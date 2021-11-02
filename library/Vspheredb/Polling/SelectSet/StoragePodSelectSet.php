<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

class StoragePodSelectSet implements SelectSet
{
    public static function create()
    {
        return [
            GenericSpec::traverseFolder([
                GenericSpec::TRAVERSE_DC_DATA_STORES,
            ]),
            GenericSpec::traverseDatacenterDataStores(),
        ];
    }
}
