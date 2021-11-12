<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use gipfl\Curl\CurlAsync;

class RpcNamespaceCurl
{
    /** @var CurlAsync */
    protected $curl;

    public function __construct(CurlAsync $curl)
    {
        $this->curl = $curl;
    }

    public function getPendingConnectionsRequest()
    {
        $handles = [];
        foreach ($this->curl->getPendingCurlHandles() as $idx => $curl) {
            $handles[$idx] = curl_getinfo($curl);
        }

        return $handles;
    }
}
