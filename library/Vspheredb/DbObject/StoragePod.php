<?php

namespace Icinga\Module\Vspheredb\DbObject;

class StoragePod extends BaseDbObject
{
    protected string|array|null $keyName = 'uuid';

    protected ?string $table = 'storage_pod';

    protected ?array $defaultProperties = [
        'uuid'         => null,
        'vcenter_uuid' => null,
        'pod_name'     => null,
        'free_space'   => null,
        'capacity'     => null,
    ];

    protected array $propertyMap = [
        'name'              => 'pod_name',
        'summary.capacity'  => 'capacity',
        'summary.freeSpace' => 'free_space',
    ];
}
