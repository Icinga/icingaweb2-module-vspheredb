<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Application\Logger;
use Icinga\Util\Format;
use SoapClient as PhpSoapClient;

/**
 * Class SoapClient
 *
 * This implementation wraps __doRequest and uses CURL for it's requests to
 * work around issues with SOCKS proxies and similar
 *
 * Use it just like the legacy PHP SoapClient, but inject a CurlLoader instance
 * in case you want to benefit from it's features. Only drawback right now: does
 * not reflect all error conditions
 */
class SoapClient extends PhpSoapClient
{
    /** @var CurlLoader */
    protected $curl;

    protected $dumpRawData = false;

    /**
     * @param CurlLoader $curl
     * @return $this
     */
    public function setCurl(CurlLoader $curl)
    {
        $this->curl = $curl;
        return $this;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        if ($this->curl === null) {
            return parent::__doRequest($request, $location, $action, $version, $one_way = 0);
        } else {
            return $this->doCurlRequest($request, $location, $action, $version, $one_way = 0);
        }
    }

    /**
     * Delegates the request to our CurlLoader
     *
     * @see PhpSoapClient::__doRequest()
     */
    public function doCurlRequest($request, $location, $action, $version, $one_way = 0)
    {
        $headers = array(
            'User-Agent'   => 'Icinga vSphere Client/1.0.3',
            'Content-Type' => 'text/xml; charset=utf-8',
            'Connection'   => 'Keep-Alive',
            'Keep-Alive'   => '300',
            'SOAPAction'   => $action,
        );
        $start = microtime(true);
        $result = $this->curl->post($location, $request, $headers);
        $duration = microtime(true) - $start;
        Logger::debug(
            'SOAPClient: sent %s, got %s response in %0.2fms',
            Format::bytes(strlen($request)),
            Format::bytes(strlen($result)),
            $duration * 1000
        );

        if ($this->dumpRawData) {
            echo "$request\n====\n$result\n";
        }

        return $result;
    }
}
