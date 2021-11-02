<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class HostSensorsPropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('HostSystem', [
                'runtime.healthSystemRuntime.systemHealthInfo.numericSensorInfo',
            ])
        ];
    }
}
