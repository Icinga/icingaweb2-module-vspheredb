<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class VmDatastoreUsagePropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('VirtualMachine', [
                'storage.perDatastoreUsage',
                'storage.timestamp'
            ])
        ];
    }
}
