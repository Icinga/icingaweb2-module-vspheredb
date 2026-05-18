<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Exception;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Zend_Db_Adapter_Exception;

abstract class MoRefList
{
    public const LIST_TABLE_NAME = 'unconfigured_table_name';

    public const MEMBER_TABLE_NAME = 'unconfigured_table_name_object';

    /**
     * @param VCenter $vCenter
     * @param ManagedObjectReference[] $objects
     *
     * @return string
     */
    public static function requireChecksum(VCenter $vCenter, array $objects): string
    {
        $key = static::calculateChecksum($vCenter, $objects);

        if (! static::checksumExists($key, $vCenter)) {
            static::create($key, $objects, $vCenter);
        }

        return $key;
    }

    /**
     * @param string $checksum
     * @param VCenter $vCenter
     *
     * @return bool
     */
    protected static function checksumExists(string $checksum, VCenter $vCenter): bool
    {
        $db = $vCenter->getDb();
        return (int) $db->fetchOne(
            $db->select()
                ->from(static::LIST_TABLE_NAME, 'COUNT(*)')
                ->where('list_checksum = ?', $checksum)
        ) > 0;
    }

    /**
     * @param string $checksum
     * @param ManagedObjectReference[] $objects
     * @param VCenter $vCenter
     *
     * @return void
     */
    protected static function create(string $checksum, array $objects, VCenter $vCenter): void
    {
        $db = $vCenter->getDb();
        $db->beginTransaction();
        try {
            $db->insert(static::LIST_TABLE_NAME, [
                'list_checksum' => $checksum
            ]);
            foreach ($objects as $object) {
                $db->insert(static::MEMBER_TABLE_NAME, [
                    'list_checksum' => $checksum,
                    'uuid' => $vCenter->makeBinaryGlobalUuid($object)
                ]);
            }
            $db->commit();
        } catch (Zend_Db_Adapter_Exception $e) {
            try {
                $db->rollBack();
            } catch (Exception $e) {
                // There is nothing we can do about this
            }

            throw $e;
        }
    }

    /**
     * @param VCenter $vCenter
     * @param ManagedObjectReference[] $objects
     *
     * @return string
     */
    protected static function calculateChecksum(VCenter $vCenter, array $objects): string
    {
        $list = [];
        foreach ($objects as $object) {
            $list[] = $vCenter->makeBinaryGlobalUuid($object);
        }
        sort($list, SORT_REGULAR); // TODO: check flag documentation

        return sha1(implode($list), true);
    }
}
