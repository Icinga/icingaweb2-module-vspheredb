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
        return array_map(fn ($curl) => curl_getinfo($curl), $this->curl->getPendingCurlHandles());
    }
}
