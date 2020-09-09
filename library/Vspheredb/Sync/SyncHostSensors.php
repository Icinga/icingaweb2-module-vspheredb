<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\HostSensor;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\MappedClass\HostNumericSensorInfo;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;

class SyncHostSensors
{
    use SyncHelper;

    public function run()
    {
        $vCenter = $this->vCenter;
        $baseKey = 'runtime.healthSystemRuntime.systemHealthInfo.numericSensorInfo';
        $result = $vCenter->getApi()->propertyCollector()->collectObjectProperties(
            new PropertySet('HostSystem', [$baseKey]),
            HostSystem::getSelectSet()
        );

        Logger::debug(
            'Got %d HostSensors with %s',
            $baseKey,
            count($result)
        );

        $connection = $vCenter->getConnection();
        $sensors = HostSensor::loadAllForVCenter($vCenter);
        Logger::debug(
            'Got %d host_sensor objects from DB',
            count($sensors)
        );

        $seen = [];
        foreach ($result as $host) {
            $uuid = $vCenter->makeBinaryGlobalUuid($host->id);
            if (! property_exists($host->$baseKey, 'HostNumericSensorInfo')) {
                // No sensor information for this host
                continue;
            }
            /** @var HostNumericSensorInfo $sensor */
            foreach ($host->$baseKey->HostNumericSensorInfo as $sensor) {
                $key = $sensor->name;
                $idx = "$uuid$key";
                $seen[$idx] = $idx;
                if (! array_key_exists($idx, $sensors)) {
                    $sensors[$idx] = HostSensor::create([
                        'host_uuid' => $uuid,
                        'name'      => $key
                    ], $connection);
                }
                $sensors[$idx]->setMapped($sensor, $vCenter);
            }
        }

        $this->storeObjects($vCenter->getDb(), $sensors, $seen);
    }
}
