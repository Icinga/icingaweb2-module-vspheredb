<?php

namespace Icinga\Module\Vspheredb\DbObject;

class VmSnapshot extends BaseDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'vm_snapshot';

    protected $defaultProperties = [
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
        'vcenter_uuid'     => null,
    ];

    protected $objectReferences = [
        'vm_uuid',
        'parent_uuid',
    ];

    protected $booleanProperties = [
        'quiesced',
        'replay_supported',
    ];

    protected $dateTimeProperties = [
        'ts_create',
    ];

    protected $propertyMap = [
        'id'              => 'id',
        'name'            => 'name',
        'description'     => 'description',
        'state'           => 'state',
        'quiesced'        => 'quiesced',
        'createTime'      => 'ts_create',
        'vm'              => 'vm_uuid',
        'parent'          => 'parent_uuid',
    ];
}
