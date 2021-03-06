<?php

namespace Icinga\Module\Vspheredb\Sync;

use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\VCenter;

trait SyncHelper
{
    /** @var VCenter */
    protected $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

    /**
     * @param \Zend_Db_Adapter_Abstract $db
     * @param \Icinga\Module\Vspheredb\Db\DbObject[] $objects
     * @param $seen
     * @throws \Icinga\Module\Vspheredb\Exception\DuplicateKeyException
     */
    protected function storeObjects(\Zend_Db_Adapter_Abstract $db, array $objects, array $seen)
    {
        $cntTotal = count($objects);
        $cntSeen = count($seen);
        $insert = 0;
        $update = 0;
        $delete = 0;
        $db->beginTransaction();
        try {
            foreach ($objects as $key => $object) {
                if (! array_key_exists($key, $seen)) {
                    $object->delete();
                    $delete++;
                } elseif ($object->hasBeenLoadedFromDb()) {
                    if ($object->hasBeenModified()) {
                        $update++;
                        $object->store();
                    }
                } else {
                    $object->store();
                    $insert++;
                }
            }

            $db->commit();
        } catch (\Exception $error) {
            try {
                $db->rollBack();
            } catch (Exception $e) {
                // There is nothing we can do.
            }
            throw $error;
        }
        Logger::debug("$insert created, $update changed, $delete deleted out of $cntTotal objects (API: $cntSeen)");
    }
}
