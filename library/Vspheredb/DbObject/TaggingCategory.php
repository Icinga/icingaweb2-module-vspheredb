<?php

namespace Icinga\Module\Vspheredb\DbObject;

class TaggingCategory extends BaseDbObject
{
    public const TABLE = 'tagging_category';
    protected $keyName = 'uuid';
    protected $table = self::TABLE;

    protected $defaultProperties = [
        'uuid'             => null,
        'vcenter_uuid'     => null,
        'id'               => null,
        'name'             => null,
        'cardinality'      => null,
        'description'      => null,
        'associable_types' => null,
    ];

    protected $propertyMap = [
        'uuid'             => 'uuid',
        'id'               => 'id',
        'name'             => 'name',
        'cardinality'      => 'cardinality',
        'description'      => 'description',
        'associable_types' => 'associable_types',
    ];

    public function cardinalityIsSingle(): bool
    {
        return $this->get('cardinality') === 'SINGLE';
    }
}
