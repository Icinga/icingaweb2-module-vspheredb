<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class VmHardwarePropertySet implements PropertySet
{
    public static function create(): array
    {
        return [
            PropertySpec::create('VirtualMachine', [
                'config.hardware',
            ])
        ];
    }
}
