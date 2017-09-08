<?php

namespace Icinga\Module\Vspheredb\DbObject;

class Datastore extends BaseDbObject
{
    protected $table = 'datastore';

    protected $defaultProperties = [
        'id'                   => null,
        'maintenance_mode'     => null,
        // capacity and free_space require accessible datastore
        'capacity'             => null,
        'free_space'           => null,
        'uncommitted'          => null,
        'is_accessible'        => null,
        'multiple_host_access' => null,
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
    ];
}
