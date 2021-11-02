<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class VmDiskUsagePropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('VirtualMachine', [
                'guest.disk',
            ])
        ];
    }
}
