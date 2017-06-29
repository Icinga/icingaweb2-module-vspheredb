<?php

namespace Icinga\Module\Vsphere;

use SoapClient;

class MySoapClient extends SoapClient
{
    /** @var CurlLoader */
    protected $curl;

    public function setCurl(CurlLoader $curl)
    {
        $this->curl = $curl;
        return $this;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $headers = array(
            'User-Agent'   => 'Icinga vSphere Client/1.0.0',
            'Content-Type' => 'text/xml; charset=utf-8',
            'Connection'   => 'Keep-Alive',
            'Keep-Alive'   => '300',
            'SOAPAction'   => $action,
        );

        $result = $this->curl->post($location, $request, $headers);
        return $result;
    }
}
