<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class DatastorePropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('Datastore', [
                'summary.maintenanceMode', // "normal"
                'summary.accessible',
                'summary.freeSpace',
                'summary.capacity',
                'summary.uncommitted',
                'summary.multipleHostAccess',
                // 'host',          // DatastoreHostMount[]
                // 'info',          // DataStoreInfo
            ])
        ];
    }
}
