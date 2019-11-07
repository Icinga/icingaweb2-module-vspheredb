<?php

namespace Icinga\Module\Vspheredb\DbObject;

class StoragePod extends BaseDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'storage_pod';

    protected $defaultProperties = [
        'uuid'         => null,
        'vcenter_uuid' => null,
        'pod_name'     => null,
        'free_space'   => null,
        'capacity'     => null,
    ];

    protected $propertyMap = [
        'name'              => 'pod_name',
        'summary.capacity'  => 'capacity',
        'summary.freeSpace' => 'free_space',
    ];
}
