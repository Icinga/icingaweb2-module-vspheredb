<?php

namespace Icinga\Module\Vspheredb\Polling;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use gipfl\Curl\CurlAsync;
use Icinga\Module\Vspheredb\Daemon\RemoteApi;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class ApiConnectionHandler implements EventEmitterInterface
{
    use EventEmitterTrait;

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

    /** @var RemoteApi */
    protected $remoteApi;

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
                $this->applyServers();
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

    protected function applyServers()
    {
        $vCenterCandidates = [];
        foreach ($this->servers->getServers() as $server) {
            if (! $server->isEnabled()) {
                continue;
            }
            $vCenterId = $server->get('vcenter_id');
            if (!isset($vCenterCandidates[$vCenterId])) {
                $vCenterCandidates[$vCenterId] = [];
            }
            $vCenterCandidates[$vCenterId][$server->get('id')] = $server;
        }

        $this->vCenterCandidates = $vCenterCandidates;
        $this->removeUnConfiguredApiConnections();
        $this->launchNewlyConfiguredVCenters();
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
                        $this->emit('apiConnection', [$connection]);
                    });
                    $this->apiConnections[$vCenterId] = $apiConnection;

                    $this->logger->notice(sprintf(
                        'Launching server %d: %s',
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
                $connection->stop();
            }
        }
        foreach ($remove as $vCenterId => $connection) {
            $this->logger->notice(
                'Removed vCenter connection for ' . $this->vCenterConnectionLogName($vCenterId, $connection)
            );
            // $this->remoteApi->removeApiConnection($connection);
            unset($this->apiConnections[$vCenterId]);
        }
    }

    protected function createApiConnection(ServerInfo $server)
    {
        return new ApiConnection($this->curl, $server, $this->logger);
    }

    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->applyServers();
    }
}
