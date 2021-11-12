<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class StoragePodPropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('StoragePod', [
                'name',
                'summary.capacity',
                'summary.freeSpace',
            ])
        ];
    }
}
