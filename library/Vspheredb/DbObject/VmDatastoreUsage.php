<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db\DbObject;

class VmDatastoreUsage extends DbObject
{
    protected string|array|null $keyName = ['vm_uuid', 'datastore_uuid'];

    protected ?string $table = 'vm_datastore_usage';

    protected ?array $defaultProperties = [
        'vm_uuid'        => null,
        'datastore_uuid' => null,
        'vcenter_uuid'   => null,
        'committed'      => null,
        'uncommitted'    => null,
        'unshared'       => null,
        'ts_updated'     => null,
    ];

    /**
     * @param VCenter $vCenter
     *
     * @return static[]
     */
    public static function loadAllForVCenter(VCenter $vCenter): array
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
            $result[$object->get('vm_uuid') . $object->get('datastore_uuid')] = $object;
        }

        return $result;
    }
}
