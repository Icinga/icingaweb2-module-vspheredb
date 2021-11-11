<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

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
     * @param \Icinga\Module\Vspheredb\Polling\ServerSet $servers
     * @return bool
     */
    public function setServersRequest(ServerSet $servers)
    {
        $this->apiConnectionHandler->setServerSet($servers);

        return true;
    }

    /**
     * @return object
     */
    public function getApiConnectionsRequest()
    {
        $result = [];
        foreach ($this->apiConnectionHandler->getApiConnections() as $api) {
            $serverInfo = $api->getServerInfo();
            $result[spl_object_hash($api)] = (object) [
                'vcenter_id' => $serverInfo->get('vcenter_id'),
                'server_id'  => $serverInfo->get('id'),
                'server'     => $serverInfo->getIdentifier(),
                'state'      => $api->getState(),
            ];
        }

        return (object) $result;
    }
}
