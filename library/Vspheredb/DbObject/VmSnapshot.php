<?php

namespace Icinga\Module\Vspheredb\DbObject;

class VmSnapshot extends BaseDbObject
{
    protected string|array|null $keyName = 'uuid';

    protected ?string $table = 'vm_snapshot';

    protected ?array $defaultProperties = [
        'uuid'            => null,
        'parent_uuid'      => null,
        'vm_uuid'          => null,
        'id'               => null,
        'moref'            => null,
        'name'             => null,
        'description'      => null,
        'ts_create'        => null,
        'state'            => null,
        'quiesced'         => null,
        'vcenter_uuid'     => null
    ];

    protected array $objectReferences = [
        'vm_uuid',
        'parent_uuid'
    ];

    protected array $booleanProperties = [
        'quiesced',
        'replay_supported'
    ];

    protected array $dateTimeProperties = [
        'ts_create'
    ];

    protected array $propertyMap = [
        'id'              => 'id',
        'name'            => 'name',
        'description'     => 'description',
        'state'           => 'state',
        'quiesced'        => 'quiesced',
        'createTime'      => 'ts_create',
        'vm'              => 'vm_uuid',
        'parent'          => 'parent_uuid'
    ];
}
