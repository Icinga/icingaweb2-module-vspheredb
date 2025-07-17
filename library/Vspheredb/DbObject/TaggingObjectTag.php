<?php

namespace Icinga\Module\Vspheredb\DbObject;

class TaggingObjectTag extends BaseDbObject
{
    public const TABLE = 'tagging_object_tag';

    protected $keyName = ['object_uuid', 'tag_uuid'];

    protected $table = self::TABLE;

    protected $defaultProperties = [
        'object_uuid'  => null,
        'tag_uuid'     => null,
        'vcenter_uuid' => null,
    ];

    protected $propertyMap = [
        'object_uuid' => 'object_uuid',
        'tag_uuid'    => 'tag_uuid',
    ];
}
