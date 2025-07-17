<?php

namespace Icinga\Module\Vspheredb\DbObject;

class TaggingTag extends BaseDbObject
{
    public const TABLE = 'tagging_tag';

    protected $keyName = 'uuid';

    protected $table = self::TABLE;

    protected $defaultProperties = [
        'uuid'             => null,
        'category_uuid'    => null,
        'vcenter_uuid'     => null,
        'id'               => null,
        'name'             => null,
        'description'      => null,
    ];

    protected $propertyMap = [
        'uuid'             => 'uuid',
        'category_uuid'    => 'category_uuid',
        'id'               => 'id',
        'name'             => 'name',
        'description'      => 'description',
    ];

    public static function loadAllForVCenter(VCenter $vCenter)
    {
        $dummy = new static();
        $connection = $vCenter->getConnection();

        return static::loadAll(
            $connection,
            $vCenter->getDb()
                ->select()
                ->from($dummy->getTableName())
                ->where('vcenter_uuid = ?', $connection->quoteBinary($vCenter->get('uuid'))),
            $dummy->keyName
        );
    }
}
