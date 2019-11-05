<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db\DbObject;

class VmDiskUsage extends DbObject
{
    protected $keyName = ['vm_uuid', 'disk_path'];

    protected $table = 'vm_disk_usage';

    protected $defaultProperties = [
        'vm_uuid'      => null,
        'disk_path'    => null,
        'capacity'     => null,
        'free_space'   => null,
        'vcenter_uuid' => null,
    ];

    /**
     * @param VCenter $vCenter
     * @return static[]
     */
    public static function loadAllForVCenter(VCenter $vCenter)
    {
        $dummy = new static();
        $objects = static::loadAll(
            $vCenter->getConnection(),
            $vCenter->getDb()
                ->select()
                ->from($dummy->getTableName())
                ->where('vcenter_uuid = ?', $vCenter->get('uuid'))
        );

        $result = [];
        foreach ($objects as $object) {
            $result[$object->get('vm_uuid') . $object->get('disk_path')] = $object;
        }

        return $result;
    }
}
