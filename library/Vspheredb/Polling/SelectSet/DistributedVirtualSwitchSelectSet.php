<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

class DistributedVirtualSwitchSelectSet implements SelectSet
{
    public static function create(): array
    {
        return [
            GenericSpec::traverseFolder([
                GenericSpec::TRAVERSE_DC_NETWORKS
            ]),
            GenericSpec::traverseDatacenterNetworks()
        ];
    }
}
