<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

class DatastoreSelectSet implements SelectSet
{
    const TRAVERSE_STORAGE_POD = 'TraverseStoragePod';

    public static function create()
    {
        return [
            GenericSpec::traverseFolder([
                GenericSpec::TRAVERSE_DC_DATA_STORES,
                self::TRAVERSE_STORAGE_POD,
            ]),
            GenericSpec::traverseDatacenterDataStores(),
            GenericSpec::traverse(self::TRAVERSE_STORAGE_POD, 'StoragePod', 'childEntity'),
        ];
    }
}
