<?php

namespace Icinga\Module\Vspheredb\PerformanceData;

use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\Util;

class IcingaRrdWriter
{
    protected $vCenter;

    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    public function send($measurement, $fields, $tags = [])
    {
        $plain = $measurement
            . $this->prepareTags($tags)
            . $this->prepareFields($fields)
            . $this->getCurrentTimestamp();
    }

    protected function getCurrentTimestamp()
    {
        return (string) Util::currentTimestamp() . '0000000';
    }

    protected function prepareFields($fields)
    {
        $plain = '';
        foreach ($fields as $k => $v) {
            if (is_int($v)) {
                $plain .= " $k=${v}i";
            } elseif (is_float($v)) {
                $plain .= " $k=$v";
            } elseif (is_bool($v)) {
                $plain .= ' ' . ($v ? 'true' : 'false');
            }
        }

        return $plain;
    }

    protected function prepareTags($tags)
    {
        if (empty($tags)) {
            return '';
        }

        $result = '';
        ksort($tags);
        foreach ($tags as $k => $v) {
            $result .= ','
                . $this->escapeString($k)
                . '='
                . $this->escapeString($v);
        }

        return $result;
    }

    protected function prepareFields($fields)
    {

    }

    protected function escapeString($string)
    {
        return \addcslashes($string, '\\,=');
    }

    /**
     * @param $plain
     * @throws IcingaException
     */
    protected function postViaInflux($plain)
    {
        $host = '<host>';
        $port = 8086;
        $db = 'icinga2_vspheredb';
        $url = sprintf('http://%s:%d/write?db=%s', $host, $port, $db);
        $ch = curl_init();
        $sendHeaders = [
            'Host: ' . $host,//  . ':' . $port,
            'Content-Type: text/plain',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $plain);
        curl_setopt($ch, CURLOPT_PROXY, 'localhost:8080');
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

        $result = curl_exec ($ch);
        if (false === $result) {
            throw new IcingaException(
                'Failed to post to InfluxDB: %s',
                curl_error($ch)
            );
        }
    }
}
