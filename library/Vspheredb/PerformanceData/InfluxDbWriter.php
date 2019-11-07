<?php

namespace Icinga\Module\Vspheredb\PerformanceData;

use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VmDiskUsage;
use Icinga\Module\Vspheredb\Util;

class InfluxDbWriter
{
    /** @var VCenter */
    protected $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

    /**
     * @param VmDiskUsage[] $usage
     * @throws IcingaException
     */
    public function sendVmDiskUsage($usage)
    {
        $plain = $this->prepareLineProtocol($usage);
        Logger::debug(sprintf('Prepared %s bytes for InfluxDB', strlen($plain)));
        $this->postToInflux($plain);
        Logger::debug('Sent all to InfluxDB');
    }

    /**
     * @param VmDiskUsage[] $usage
     * @return string
     * @throws IcingaException
     */
    protected function prepareLineProtocol($usage)
    {
        $db = $this->vCenter->getDb();
        $lookup = $db->fetchPairs(
            $db->select()->from('object', ['uuid', 'object_name'])
        );
        $plain = '';
        $id = $this->vCenter->get('id');
        $now = Util::currentTimestamp();
        foreach ($usage as $disk) {
            $uuid = $disk->get('vm_uuid');
            if (! array_key_exists($uuid, $lookup)) {
                continue;
            }

            // Influx Line Protocol:
            $plain .= sprintf(
                "vm_disk_usage,vm=%s,path=\"%s\",vCenterId=%s free_space=%si,capacity=%si %s000000\n",
                $lookup[$uuid],
                addcslashes($disk->get('disk_path'), '\\,'),
                $id,
                $disk->get('free_space'),
                $disk->get('capacity'),
                $now
            );
        }

        return $plain;
    }

    /**
     * @param $plain
     * @throws IcingaException
     */
    protected function postToInflux($plain)
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
