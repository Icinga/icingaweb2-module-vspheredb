<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Curl\CurlAsync;
use gipfl\Protocol\JsonRpc\Handler\RpcContext;
use gipfl\Protocol\JsonRpc\Handler\RpcUserInfo;
use gipfl\Protocol\JsonRpc\Request;

class RpcContextCurl extends RpcContext
{
    /** @var CurlAsync */
    protected $curl;

    public function __construct(CurlAsync $curl, RpcUserInfo $userInfo)
    {
        $this->curl = $curl;
        parent::__construct($userInfo);
    }

    public function getNamespace()
    {
        return 'curl';
    }

    public function isAccessible()
    {
        return true;
    }

    /**
     * @param Request $request
     */
    public function getPendingConnectionsRequest(Request $request)
    {
        $handles = [];
        foreach ($this->curl->getPendingCurlHandles() as $idx => $curl) {
            $handles[$idx] = curl_getinfo($curl);
        }

        return $handles;
    }
}
