<?php

namespace Icinga\Module\Vspheredb\Polling;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use gipfl\Curl\CurlAsync;
use Icinga\Module\Vspheredb\MappedClass\AboutInfo;
use Icinga\Module\Vspheredb\MappedClass\ServiceContent;
use Icinga\Module\Vspheredb\Monitoring\Health\ApiConnectionInfo;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;

class ApiConnectionHandler implements EventEmitterInterface
{
    use EventEmitterTrait;

    const ON_INITIALIZED_SERVER = 'initialized';
    const ON_CONNECT = 'connection';
    const ON_DISCONNECT = 'disconnect';

    /** @var CurlAsync */
    protected $curl;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ?LoopInterface */
    protected $loop;

    /** @var ServerSet */
    protected $servers;

    /** @var ApiConnection[]  $vcenterId => ApiConnection */
    protected $apiConnections = [];

    /** @var array<int, array<int, ServerInfo>> [vcenterId => [serverId => ServerInfo, ...] */
    protected $vCenterCandidates = [];

    /** @var array<int, Deferred> [serverId => Deferred] */
    protected $initializations = [];

    /** @var array<int, TimerInterface> key is the serverId */
    protected $failing = [];

    /** @var array<int, string> key is the serverId */
    protected $failingErrorMessages = [];

    /** @var ServerSet */
    protected $appliedServers;

    public function __construct(CurlAsync $curl, LoggerInterface $logger)
    {
        // $this->remoteApi = $remoteApi;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->servers = new ServerSet();
        $this->appliedServers = new ServerSet();
    }

    public function setServerSet(ServerSet $servers)
    {
        if (!$servers->equals($this->servers)) {
            $this->servers = $servers;
            if ($this->loop) {
                $this->applyServers($this->servers);
            }
        }
    }

    public function getConnectionForVcenterId($id): ?ApiConnection
    {
        if (isset($this->apiConnections[$id])) {
            return $this->apiConnections[$id];
        }

        return null;
    }

    /**
     * @return ApiConnectionInfo[]
     */
    public function getApiConnectionOverview(): array
    {
        /** @var array<int, ApiConnectionInfo> $connections */
        $connections = [];
        foreach ($this->apiConnections as $connection) {
            $connections[] = ApiConnectionInfo::fromConnectionInfo($connection);
        }

        foreach ($this->failingErrorMessages as $serverId => $message) {
            $connections[] = ApiConnectionInfo::failingConnectionForServer(
                $this->servers->getServer($serverId),
                $message
            );
        }

        return $connections;
    }

    protected function applyServers(ServerSet $servers)
    {
        if ($servers->equals($this->appliedServers)) {
            $this->logger->debug('Server Set is unchanged');
            return;
        }
        $vCenterCandidates = [];
        foreach ($servers->getServers() as $server) {
            $serverId = $server->getServerId();
            $vCenterId = $server->getVCenterId();
            if ($vCenterId === null && $server->isEnabled()) {
                if (! isset($this->initializations[$serverId])) {
                    $this->startInitialization($server);
                }

                continue;
            }
            if (!isset($vCenterCandidates[$vCenterId])) {
                $vCenterCandidates[$vCenterId] = [];
            }
            $vCenterCandidates[$vCenterId][$serverId] = $server;
        }

        $this->vCenterCandidates = $vCenterCandidates;
        $this->removeUnConfiguredApiConnections();
        $this->launchNewlyConfiguredVCenters();
        $this->appliedServers = $servers;
        $this->removeObsoleteFailingServers();
    }

    protected function startInitialization(ServerInfo $server)
    {
        $serverId = $server->getServerId();
        $this->initializations[$serverId] = $initialize = $this->initialize($server);

        $initialize->promise()->then(function ($initialized) use ($server) {
            /** @var AboutInfo $content */
            /** @var UuidInterface $uuid */
            list($about, $uuid) = $initialized;
            $this->emit(self::ON_INITIALIZED_SERVER, [$server, $about, $uuid]);
        }, function (Exception $e) use ($serverId) {
            unset($this->initializations[$serverId]);
            $this->logger->error('Initialization failed: ' . $e->getMessage());
        });
    }

    protected function initialize(ServerInfo $server): Deferred
    {
        $apiConnection = $this->createApiConnection($server);
        $deferred = new Deferred(function () use ($apiConnection) {
            $apiConnection->stop();
        });
        $apiConnection->on(ApiConnection::ON_READY, function (ApiConnection $connection) use ($server, $deferred) {
            $connection->getApi()
                ->fetchUniqueId()
                ->then(function (UuidInterface $uuid) use ($connection, $server, $deferred) {
                    return $connection->getApi()
                        ->getServiceInstance()
                        ->then(function (ServiceContent $content) use ($server, $uuid, $deferred, $connection) {
                            $connection->stop();
                            $deferred->resolve([$content->about, $uuid]);
                        });
                }, function (Exception $e) use ($deferred) {
                    $this->logger->error($e->getMessage());
                    $deferred->reject($e);
                });
        });
        $apiConnection->on(ApiConnection::ON_ERROR, function (ApiConnection $connection) use ($server, $deferred) {
            if ($error = $connection->getLastErrorMessage()) {
                $message = "Initialization failed: $error";
            } else {
                $message = 'Initialization failed';
            }
            $deferred->reject(new Exception($message));
            $this->setFailed($server, $message);
        });
        $this->logger->notice(sprintf(
            '[api] initializing server %d: %s',
            $server->getServerId(),
            $server->getIdentifier()
        ));
        $apiConnection->run($this->loop);

        return $deferred;
    }

    protected function launchNewlyConfiguredVCenters()
    {
        foreach ($this->vCenterCandidates as $vCenterId => $servers) {
            /** @var ServerInfo $server */
            if (isset($this->apiConnections[$vCenterId])) {
                foreach ($servers as $server) {
                    if ($server->equals($this->apiConnections[$vCenterId]->getServerInfo())) {
                        // vCenter is covered, the running Server Config is still active
                        continue 2;
                    }
                }
            }
            foreach ($servers as $server) {
                if (! $server->isEnabled()) {
                    continue;
                }
                if (isset($this->failing[$server->getServerId()])) {
                    continue;
                }
                if (! isset($this->apiConnections[$vCenterId])) {
                    $apiConnection = $this->createApiConnection($server);
                    $apiConnection->on(ApiConnection::ON_READY, function (ApiConnection $connection) {
                        $this->emit(self::ON_CONNECT, [$connection]);
                    });
                    $apiConnection->on(ApiConnection::ON_ERROR, function (ApiConnection $connection) use ($vCenterId) {
                        unset($this->apiConnections[$vCenterId]);
                        // $this->logger->error('GOT AN ERROR');
                        $this->setFailed($connection->getServerInfo(), $connection->getLastErrorMessage());
                        $this->emit(self::ON_DISCONNECT, [$connection]);
                    });
                    $this->apiConnections[$vCenterId] = $apiConnection;

                    $this->logger->notice(sprintf(
                        '[api] launching server %d: %s',
                        $server->getServerId(),
                        $this->vCenterConnectionLogName($vCenterId, $apiConnection)
                    ));
                    $apiConnection->run($this->loop);
                }
            }
        }
    }

    protected function setFailed(ServerInfo $server, ?string $message = 'unknown error')
    {
        $serverId = $server->getServerId();
        $this->logger->warning(sprintf(
            'Server %s disabled for 60 seconds',
            $server->getIdentifier()
        ));
        $this->failingErrorMessages[$serverId] = $message;
        $this->failing[$serverId] = $this->loop->addTimer(60, function () use ($server) {
            $this->logger->notice('Failing over for ' . $server->getIdentifier());
            $serverId = $server->getServerId();
            if (! isset($this->failing[$serverId])) {
                $this->logger->error(sprintf('Not retrying %s, connection has been removed', $server->getIdentifier()));
                return;
            }
            $this->loop->cancelTimer($this->failing[$serverId]);
            unset($this->failing[$serverId]);
            unset($this->failingErrorMessages[$serverId]);
            $this->launchNewlyConfiguredVCenters();
        });
    }

    protected function vCenterConnectionLogName($vCenterId, ApiConnection $apiConnection): string
    {
        return sprintf(
            'vCenterId=%d: %s',
            $vCenterId,
            $apiConnection->getServerInfo()->getIdentifier()
        );
    }

    /**
     * @return array<int, true>
     */
    protected function listAppliedServers(): array
    {
        $list = [];
        foreach ($this->appliedServers->getServers() as $serverInfo) {
            if ($serverInfo->isEnabled()) {
                $list[$serverInfo->getServerId()] = true;
            }
        }

        return $list;
    }

    protected function removeObsoleteFailingServers()
    {
        $serverMap = $this->listAppliedServers();
        foreach ($this->failing as $serverId => $timer) {
            if (!isset($serverMap[$serverId])) {
                $this->loop->cancelTimer($timer);
                $this->logger->notice(sprintf('[api] removing failing server (id=%d)', $serverId));
                unset($this->failing[$serverId]);
                unset($this->failingErrorMessages[$serverId]);
            }
        }
    }

    protected function removeUnConfiguredApiConnections()
    {
        $remove = [];
        foreach ($this->apiConnections as $vCenterId => $connection) {
            if (!isset($this->vCenterCandidates[$vCenterId])) {
                $remove[$vCenterId] = $connection;
            }
            if (! $this->appliedServers->getServer($connection->getServerInfo()->getServerId())->isEnabled()) {
                $remove[$vCenterId] = $connection;
            }
        }
        foreach ($remove as $vCenterId => $connection) {
            $this->logger->notice(
                '[api] removed vCenter connection for ' . $this->vCenterConnectionLogName($vCenterId, $connection)
            );
            $connection->stop();
            unset($this->apiConnections[$vCenterId]);
            $this->emit(self::ON_DISCONNECT, [$connection]);
        }
    }

    protected function createApiConnection(ServerInfo $server): ApiConnection
    {
        return new ApiConnection($this->curl, $server, $this->logger);
    }

    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->applyServers($this->servers);
    }

    public function stop()
    {
        $this->logger->notice('Stopping API connection handler');
        $this->applyServers($this->servers = new ServerSet());
    }
}
