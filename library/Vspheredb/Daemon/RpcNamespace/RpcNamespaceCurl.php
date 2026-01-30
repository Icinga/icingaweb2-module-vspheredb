<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use gipfl\Curl\CurlAsync;

class RpcNamespaceCurl
{
    /** @var CurlAsync */
    protected CurlAsync $curl;

    /**
     * @param CurlAsync $curl
     */
    public function __construct(CurlAsync $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @return array
     */
    public function getPendingConnectionsRequest(): array
    {
        $handles = [];
        foreach ($this->curl->getPendingCurlHandles() as $idx => $curl) {
            $handles[$idx] = curl_getinfo($curl);
        }

        return $handles;
    }
}
