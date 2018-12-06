<?php

namespace Icinga\Module\Vspheredb\DbObject;

class Datastore extends BaseDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'datastore';

    protected $defaultProperties = [
        'uuid'                   => null,
        'vcenter_uuid'           => null,
        'maintenance_mode'       => null,
        // capacity and free_space require accessible datastore
        'capacity'               => null,
        'free_space'             => null,
        'uncommitted'            => null,
        'is_accessible'          => null,
        'multiple_host_access'   => null,
        'ts_last_forced_refresh' => null,
    ];

    protected $booleanProperties = [
        'is_accessible',
        'multiple_host_access',
        'ssd'
    ];

    protected $propertyMap = [
        'summary.maintenanceMode'    => 'maintenance_mode', // "normal"
        'summary.accessible'         => 'is_accessible',
        'summary.freeSpace'          => 'free_space',
        'summary.capacity'           => 'capacity',
        'summary.uncommitted'        => 'uncommitted',
        'summary.multipleHostAccess' => 'multiple_host_access',
        // 'host',          // DatastoreHostMount[]
        // 'info',          // DataStoreInfo
    ];
}
