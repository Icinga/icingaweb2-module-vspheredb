<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Protocol\JsonRpc\Handler\RpcContext;
use gipfl\Protocol\JsonRpc\Request;
use gipfl\RpcDaemon\RpcUserInfo;
use Icinga\Module\Vspheredb\Polling\ApiConnectionHandler;
use Icinga\Module\Vspheredb\Polling\ServerSet;

class RpcContextVsphere extends RpcContext
{
    /** @var ApiConnectionHandler */
    protected $apiConnectionHandler;

    public function __construct(ApiConnectionHandler $apiConnectionHandler, RpcUserInfo $userInfo)
    {
        parent::__construct($userInfo);
        $this->apiConnectionHandler = $apiConnectionHandler;
    }

    public function getNamespace()
    {
        return 'vsphere';
    }

    public function isAccessible()
    {
        return true;
    }

    /**
     * @rpcParam ServerSet $servers Server Set
     * @param Request $request
     * @return bool
     */
    public function setServersRequest(Request $request)
    {
        $this->apiConnectionHandler->setServerSet(ServerSet::fromSerialization($request->getParam('servers')));

        return true;
    }

    /**
     * @param Request $request
     * @return object
     */
    public function getApiConnectionsRequest(Request $request)
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
