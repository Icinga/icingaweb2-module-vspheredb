<?php

namespace Icinga\Module\Vspheredb\DbObject;

class Datastore extends BaseDbObject
{
    protected string|array|null $keyName = 'uuid';

    protected ?string $table = 'datastore';

    protected ?array $defaultProperties = [
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

    protected array $booleanProperties = [
        'is_accessible',
        'multiple_host_access',
        'ssd'
    ];

    protected array $propertyMap = [
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
