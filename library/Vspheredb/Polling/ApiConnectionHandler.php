<?php

namespace Icinga\Module\Vspheredb\Polling;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use gipfl\Curl\CurlAsync;
use gipfl\ReactUtils\RetryUnless;
use Icinga\Module\Vspheredb\MappedClass\ServiceContent;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;
use function React\Promise\Timer\timeout;

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

    /** @var ExtendedPromiseInterface[] [serverId => Promise] */
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
        $this->setServerSet(new ServerSet());
    }

    public function setServerSet(ServerSet $servers)
    {
        if ($this->servers === null || ! $servers->equals($this->servers)) {
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
        $vCenterCandidates = [];
        foreach ($servers->getServers() as $server) {
            if (! $server->isEnabled()) {
                continue;
            }
            $serverId = $server->get('id');
            $vCenterId = $server->get('vcenter_id');
            if ($vCenterId === null) {
                if (! isset($this->initializations[$serverId])) {
                    $this->initializations[$serverId] = RetryUnless::succeeding(function () use ($server) {
                        return timeout($this
                            ->initialize($server)
                            ->then(function (ServiceContent $content, UuidInterface $uuid) use ($server) {
                                $this->emit(self::ON_INITIALIZED_SERVER, [$server, $content->about, $uuid]);
                            }), 300, $this->loop);
                    });
                }

                continue;
            }
            if (!isset($vCenterCandidates[$vCenterId])) {
                $vCenterCandidates[$vCenterId] = [];
            }
            if (isset($this->failing[$serverId])) {
                continue;
            }
            $vCenterCandidates[$vCenterId][$serverId] = $server;
        }

        $this->vCenterCandidates = $vCenterCandidates;
        $this->removeUnConfiguredApiConnections();
        $this->launchNewlyConfiguredVCenters();
        $this->appliedServers = $servers;
    }

    protected function initialize(ServerInfo $server)
    {
        $apiConnection = $this->createApiConnection($server);
        $deferred = new Deferred(function () use ($apiConnection) {
            $apiConnection->stop();
        });
        $apiConnection->on('ready', function (ApiConnection $connection) use ($server, $deferred) {
            $connection->getApi()
                ->fetchUniqueId()
                ->then(function (UuidInterface $uuid) use ($connection, $server, $deferred) {
                    return $connection->getApi()
                        ->getServiceInstance()
                        ->then(function (ServiceContent $content) use ($server, $uuid, $deferred, $connection) {
                            $connection->stop();
                            $deferred->resolve([$content->about, $uuid]);
                        });
                }, function (\Exception $e) use ($deferred) {
                    $this->logger->error($e->getMessage());
                    $deferred->reject($e);
                });
        });
        $this->logger->notice(sprintf(
            '[api] initializing server %d: %s',
            $server->get('id'),
            $server->get('host')
        ));
        $apiConnection->run($this->loop);

        return $deferred->promise();
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
                    $apiConnection->on('ready', function (ApiConnection $connection) {
                        $this->emit(self::ON_CONNECT, [$connection]);
                    });
                    $apiConnection->on('error', function (ApiConnection $connection) use ($vCenterId) {
                        $serverId = $connection->getServerInfo()->get('id');
                        $this->logger->warning(sprintf(
                            'Server %s disabled for 60 seconds',
                            $connection->getServerInfo()->get('host')
                        ));
                        $this->failing[$serverId] = $this->loop->addTimer(60, function () use ($serverId) {
                            $this->loop->cancelTimer($this->failing[$serverId]);
                            unset($this->failing[$serverId]);
                            $this->applyServers($this->appliedServers);
                        });
                        $this->emit(self::ON_DISCONNECT, [$connection]);
                        unset($this->apiConnections[$vCenterId]);
                        $this->applyServers($this->appliedServers);
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

    protected function vCenterConnectionLogName($vCenterId, ApiConnection $apiConnection)
    {
        return sprintf(
            'vCenterId=%d: %s',
            $vCenterId,
            $apiConnection->getServerInfo()->getIdentifier()
        );
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
            // $this->remoteApi->removeApiConnection($connection);
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
        $this->applyServers(new ServerSet());
    }
}
