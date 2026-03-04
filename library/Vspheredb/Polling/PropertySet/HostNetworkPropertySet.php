<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class HostNetworkPropertySet implements PropertySet
{
    public static function create(): array
    {
        return [
            PropertySpec::create('HostSystem', [
                'config.network'
            ])
        ];
    }
}
