<?php

namespace Icinga\Module\Vspheredb\DbObject;

class Alarm extends BaseDbObject
{
    protected $table = 'alarm';

    protected $defaultProperties = [
        'system_name'        => null,
        'alarm_name'         => null,
        'description'        => null,
        'ts_last_modified'   => null,
        'user_last_modified' => null,
        'is_enabled'         => null,
        'entity_uuid'        => null,
        'expression'         => null,
        'vcenter_uuid'       => null,
    ];

    protected $objectReferences = [
        'vcenter_uuid',
        'entity_uuid',
    ];

    protected $propertyMap = [
        //'info' => 'info'
        'info.systemName'           => 'system_name',
        'info.name'                  => 'alarm_name',
        'info.description'           => 'description',
        'info.lastModifiedTime'      => 'ts_last_modified',
        'info.lastModifiedUser'      => 'user_last_modified',
        'info.enabled'               => 'is_enabled',
        'info.entity'                => 'entity_uuid',
        'info.expression' => 'expression',
    ];

    protected $keyName = ['vcenter_uuid', 'system_name'];

    protected $booleanProperties = ['is_enabled'];

    public function setInfo($info)
    {
        print_r($info);
        exit;
    }
}
