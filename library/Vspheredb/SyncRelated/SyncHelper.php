<?php

namespace Icinga\Module\Vspheredb\SyncRelated;

use Exception;
use Icinga\Module\Vspheredb\Db\DbObject;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Zend_Db_Adapter_Abstract;

trait SyncHelper
{
    /**
     * @param Zend_Db_Adapter_Abstract $db
     * @param callable $callback
     */
    protected static function runAsTransaction(Zend_Db_Adapter_Abstract $db, callable $callback): void
    {
        $db->beginTransaction();
        try {
            $callback();
            $db->commit();
        } catch (Exception $error) {
            try {
                $db->rollBack();
            } catch (Exception $e) {
                // There is nothing we can do.
            }

            throw $error;
        }
    }

    /**
     * @param Zend_Db_Adapter_Abstract $db
     * @param DbObject[] $dbObjects
     * @param array $apiObjects
     * @param SyncStats $stats
     *
     * @return void
     *
     * @throws Exception
     */
    protected function storeSyncObjects(
        Zend_Db_Adapter_Abstract $db,
        array $dbObjects,
        array $apiObjects,
        SyncStats $stats
    ): void {
        $create = [];
        self::runAsTransaction($db, function () use ($apiObjects, &$dbObjects, $stats, &$create) {
            $modify = [];
            $delete = [];
            foreach ($dbObjects as $idx => $object) {
                if (!array_key_exists($idx, $apiObjects)) {
                    $delete[] = $object;
                } elseif ($object->hasBeenLoadedFromDb()) {
                    if ($object->hasBeenModified()) {
                        $modify[] = $object;
                    }
                } else {
                    $create[] = $object;
                }
            }

            foreach ($modify as $object) {
                $object->store();
                $stats->incModified();
            }
            foreach ($delete as $object) {
                $object->delete();
                $stats->incDeleted();
            }
            foreach ($create as $dbObject) {
                $dbObject->store();
                $stats->incCreated();
            }
        });
        $stats->setFromDb(count($dbObjects) - count($create));
        $stats->setFromApi(count($apiObjects));
    }

    /**
     * @param class-string<BaseDbObject> $class
     * @param string $table
     * @param VCenter $vCenter
     *
     * @return array
     *
     * @return BaseDbObject[]
     */
    protected static function loadAllForVCenter(string $class, string $table, VCenter $vCenter): array
    {
        return $class::loadAll(
            $vCenter->getConnection(),
            $vCenter->getDb()
                ->select()
                ->from($table)
                ->where('vcenter_uuid = ?', $vCenter->get('uuid')),
            'uuid'
        );
    }
}
