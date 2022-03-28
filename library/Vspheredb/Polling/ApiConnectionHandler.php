<?php

namespace Icinga\Module\Vspheredb\Polling;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use gipfl\Curl\CurlAsync;
use Icinga\Module\Vspheredb\MappedClass\AboutInfo;
use Icinga\Module\Vspheredb\MappedClass\ServiceContent;
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

    /** @var array [vcenterId => [ServerInfo, ...] */
    protected $vCenterCandidates = [];

    /** @var Deferred[] [serverId => Deferred] */
    protected $initializations = [];

    /** @var TimerInterface[] [serverId => TimerInterface] */
    protected $failing = [];

    /** @var ServerSet */
    protected $appliedServers;

    public function __construct(CurlAsync $curl, LoggerInterface $logger)
    {
        // $this->remoteApi = $remoteApi;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->servers = $this->appliedServers = new ServerSet();
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

    public function getConnectionForVcenterId($id)
    {
        if (isset($this->apiConnections[$id])) {
            return $this->apiConnections[$id];
        }

        return null;
    }

    public function getApiConnections()
    {
        return $this->apiConnections;
    }

    protected function applyServers(ServerSet $servers)
    {
        if ($servers->equals($this->appliedServers)) {
            $this->logger->debug('Server Set is unchanged');
            return;
        }
        $vCenterCandidates = [];
        foreach ($servers->getServers() as $server) {
            $serverId = $server->get('id');
            // Hint: isEnabled is not required, we apply only enabled ones
            if (! $server->isEnabled() || isset($this->failing[$serverId])) {
                continue;
            }
            $vCenterId = $server->get('vcenter_id');
            if ($vCenterId === null) {
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
        $serverId = $server->get('id');
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

    protected function initialize(ServerInfo $server)
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
            $deferred->reject(new Exception('Initialization failed'));
            $this->setFailed($server);
        });
        $this->logger->notice(sprintf(
            '[api] initializing server %d: %s',
            $server->get('id'),
            $server->get('host')
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
                if (! isset($this->apiConnections[$vCenterId])) {
                    $apiConnection = $this->createApiConnection($server);
                    $apiConnection->on(ApiConnection::ON_READY, function (ApiConnection $connection) {
                        $this->emit(self::ON_CONNECT, [$connection]);
                    });
                    $apiConnection->on(ApiConnection::ON_ERROR, function (ApiConnection $connection) use ($vCenterId) {
                        unset($this->apiConnections[$vCenterId]);
                        // $this->logger->error('GOT AN ERROR');
                        $this->setFailed($connection->getServerInfo());
                        $this->emit(self::ON_DISCONNECT, [$connection]);
                    });
                    $this->apiConnections[$vCenterId] = $apiConnection;

                    $this->logger->notice(sprintf(
                        '[api] launching server %d: %s',
                        $server->get('id'),
                        $this->vCenterConnectionLogName($vCenterId, $apiConnection)
                    ));
                    $apiConnection->run($this->loop);
                }
            }
        }
    }

    protected function setFailed(ServerInfo $server)
    {
        $serverId = $server->get('id');
        $this->logger->warning(sprintf(
            'Server %s disabled for 60 seconds',
            $server->get('host')
        ));
        $this->failing[$serverId] = $this->loop->addTimer(60, function () use ($server) {
            $serverId = $server->get('id');
            if (! isset($this->failing[$serverId])) {
                $this->logger->error(sprintf('Not retrying %s, connection has been removed', $server->getIdentifier()));
                return;
            }
            $this->loop->cancelTimer($this->failing[$serverId]);
            unset($this->failing[$serverId]);
            $this->applyServers($this->appliedServers);
        });
    }

    protected function vCenterConnectionLogName($vCenterId, ApiConnection $apiConnection)
    {
        return sprintf(
            'vCenterId=%d: %s',
            $vCenterId,
            $apiConnection->getServerInfo()->getIdentifier()
        );
    }

    protected function listAppliedServers()
    {
        $list = [];
        foreach ($this->appliedServers->getServers() as $serverInfo) {
            if ($serverInfo->isEnabled()) {
                $list[$serverInfo->get('id')] = true;
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

    protected function createApiConnection(ServerInfo $server)
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
