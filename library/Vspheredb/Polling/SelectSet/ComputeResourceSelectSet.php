<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

class ComputeResourceSelectSet implements SelectSet
{
    public static function create()
    {
        return [
            GenericSpec::traverseFolder([
                GenericSpec::TRAVERSE_DC_HOST_SYSTEMS
            ]),
            GenericSpec::traverseDatacenterHosts(),
        ];
    }
}
