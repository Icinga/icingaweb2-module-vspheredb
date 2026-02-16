<?php

namespace Icinga\Module\Vspheredb\DbObject;

class TaggingObjectTag extends BaseDbObject
{
    public const TABLE = 'tagging_object_tag';

    protected string|array|null $keyName = ['object_uuid', 'tag_uuid'];

    protected ?string $table = self::TABLE;

    protected ?array $defaultProperties = [
        'object_uuid'  => null,
        'tag_uuid'     => null,
        'vcenter_uuid' => null
    ];

    protected array $propertyMap = [
        'object_uuid' => 'object_uuid',
        'tag_uuid'    => 'tag_uuid'
    ];
}
