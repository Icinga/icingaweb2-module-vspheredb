<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class HostVirtualNicPropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('HostSystem', [
                'config.network.vnic',
            ])
        ];
    }
}
