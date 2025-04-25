<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

class VirtualMachineSelectSet implements SelectSet
{
    public const TRAVERSE_VIRTUAL_APP = 'TraverseVirtualApp';

    public static function create()
    {
        return [
            GenericSpec::traverseFolder([
                self::TRAVERSE_VIRTUAL_APP,
                GenericSpec::TRAVERSE_DC_VIRTUAL_MACHINES,
            ]),
            GenericSpec::traverseDatacenterVirtualMachines(),
            GenericSpec::traverse(self::TRAVERSE_VIRTUAL_APP, 'VirtualApp', 'vm'),
        ];
    }
}
