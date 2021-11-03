<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\DbObject\VmSnapshot;
use Icinga\Module\Vspheredb\SyncRelated\SyncHelper;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;

class VmSnapshotSyncStore extends SyncStore
{
    use SyncHelper;

    public function store($result, $class, SyncStats $stats)
    {
        $vCenter = $this->vCenter;
        $vCenterUuid = $vCenter->getUuid();
        $connection = $vCenter->getConnection();
        $dbObjects = VmSnapshot::loadAllForVCenter($vCenter);

        $seen = [];
        foreach ($result as $object) {
            $object = (object) $object;
            if (! property_exists($object, 'snapshot')) {
                continue;
            }

            // TODO: should we store the snapshot->currentSnapshot (ref)?
            $snapshots = $this->flattenSnapshots($object->snapshot->rootSnapshotList);
            foreach ($snapshots as $snapshot) {
                $idx = $vCenter->makeBinaryGlobalMoRefUuid($snapshot->snapshot);
                $seen[$idx] = $idx;
                if (! array_key_exists($idx, $dbObjects)) {
                    $dbObjects[$idx] = VmSnapshot::create([
                        'uuid' => $idx,
                        'moref' => $snapshot->snapshot->_,
                        'vcenter_uuid' => $vCenterUuid
                    ], $connection);
                }
                $dbObjects[$idx]->setMapped($snapshot, $vCenter);
            }
        }
        $this->storeSyncObjects($connection->getDbAdapter(), $dbObjects, $seen, $stats);
    }

    protected function flattenSnapshots($snapshots, $parent = null)
    {
        $append = [];

        foreach ($snapshots as $snapshot) {
            $snapshot->parent = $parent;

            if (property_exists($snapshot, 'childSnapshotList')) {
                $append = $this->flattenSnapshots(
                    $snapshot->childSnapshotList,
                    $snapshot->snapshot
                );
                unset($snapshot->childSnapshotList);
            }
        }

        foreach ($append as $snapshot) {
            $snapshots[] = $snapshot;
        }

        return $snapshots;
    }
}
