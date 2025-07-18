<?php

namespace Icinga\Module\Vspheredb\PerformanceData;

use gipfl\Curl\CurlAsync;
use gipfl\InfluxDb\ChunkedInfluxDbWriter;
use gipfl\InfluxDb\InfluxDbConnection;
use gipfl\InfluxDb\InfluxDbConnectionFactory;
use gipfl\InfluxDb\InfluxDbConnectionV1;
use gipfl\InfluxDb\InfluxDbConnectionV2;
use gipfl\Json\JsonString;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Hook\PerfDataConsumerHook;
use Icinga\Module\Vspheredb\Storable\PerfdataConsumer;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

class InfluxConnectionForVcenterLoader
{
    /**
     * @return PromiseInterface<?ChunkedInfluxDbWriter>
     */
    public static function load(VCenter $vCenter, CurlAsync $curl, LoopInterface $loop): ?PromiseInterface
    {
        $db = $vCenter->getConnection()->getDbAdapter();
        $row = $db->fetchRow($db->select()->from([
            'pc' => 'perfdata_consumer'
        ], 'pc.*')->join([
            'ps' => 'perfdata_subscription'
        ], $db->quoteInto('ps.consumer_uuid = pc.uuid AND ps.vcenter_uuid = ?', $vCenter->get('instance_uuid')), [
            'vcenter_settings' => 'ps.settings'
        ]));
        if (! $row) {
            return resolve(null);
        }
        $vCenterSettings = JsonString::decode($row->vcenter_settings);
        unset($row->vcenter_settings);
        $instance = PerfDataConsumerHook::createConsumerInstance(
            PerfdataConsumer::create((array) $row),
            $loop
        );
        switch ($instance->getSetting('api_version')) {
            case 'v1':
                $influxDb = resolve(new InfluxDbConnectionV1(
                    $curl,
                    $instance->getSetting('base_url'),
                    $instance->getSetting('username'),
                    $instance->getSetting('password')
                ));
                break;
            case 'v2':
                $influxDb = resolve(new InfluxDbConnectionV2(
                    $curl,
                    $instance->getSetting('base_url'),
                    $instance->getSetting('username'),
                    $instance->getSetting('password')
                    // $instance->getSetting('organization'),
                    // $instance->getSetting('token')
                ));
                break;
            default:
                $influxDb = InfluxDbConnectionFactory::create(
                    $curl,
                    $instance->getSetting('base_url'),
                    $instance->getSetting('username'),
                    $instance->getSetting('password')
                );
        }

        return $influxDb->then(function (InfluxDbConnection $influxDb) use ($vCenterSettings, $loop) {
            $influxDbWriter = new ChunkedInfluxDbWriter(
                $influxDb,
                $vCenterSettings->dbname,
                $loop
            );
            $influxDbWriter->setPrecision('s');

            return $influxDbWriter;
        });
    }
}
