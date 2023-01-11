<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use Icinga\Module\Vspheredb\Monitoring\Health\ApiConnectionInfo;
use Icinga\Module\Vspheredb\Polling\ApiConnectionHandler;
use Icinga\Module\Vspheredb\Polling\ServerSet;

class RpcNamespaceVsphere
{
    /** @var ApiConnectionHandler */
    protected $apiConnectionHandler;

    public function __construct(ApiConnectionHandler $apiConnectionHandler)
    {
        $this->apiConnectionHandler = $apiConnectionHandler;
    }

    /**
     * Hint: Full qualified reference is necessary for RPC type check
     *
     * @param \Icinga\Module\Vspheredb\Polling\ServerSet $servers
     * @return bool
     */
    public function setServersRequest(ServerSet $servers): bool
    {
        $this->apiConnectionHandler->setServerSet($servers);

        return true;
    }

    /**
     * @return ApiConnectionInfo[]
     */
    public function getApiConnectionsRequest(): array
    {
        return $this->apiConnectionHandler->getApiConnectionOverview();
    }
}
