<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

class DistributedVirtualPortgroupSelectSet implements SelectSet
{
    public static function create()
    {
        return [
            GenericSpec::traverseFolder([
                GenericSpec::TRAVERSE_DC_NETWORKS
            ]),
            GenericSpec::traverseDatacenterNetworks(),
        ];
    }
}
