<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmSnapshot;
use Icinga\Module\Vspheredb\PropertySet\PropertySet;

class SyncVmSnapshots
{
    use SyncHelper;

    public function run()
    {
        $vCenter = $this->vCenter;
        $vCenterUuid = $vCenter->get('uuid');
        $result = $vCenter->getApi()->propertyCollector()->collectObjectProperties(
            new PropertySet('VirtualMachine', ['snapshot']),
            VirtualMachine::getSelectSet()
        );

        Logger::debug(
            'Got %d VirtualMachines with snapshot',
            count($result)
        );

        $connection = $vCenter->getConnection();
        $existing = VmSnapshot::loadAllForVCenter($vCenter);
        Logger::debug(
            'Got %d vm_snapshot objects from DB',
            count($existing)
        );

        $seen = [];
        foreach ($result as $vm) {
            if (! property_exists($vm, 'snapshot')) {
                continue;
            }

            // TODO: should we store the snapshot->currentSnapshot (ref)?
            $snapshots = $this->flattenSnapshots($vm->snapshot->rootSnapshotList);
            foreach ($snapshots as $snapshot) {
                $idx = $vCenter->makeBinaryGlobalUuid($snapshot->snapshot->_);
                $seen[$idx] = $idx;
                if (! array_key_exists($idx, $existing)) {
                    $existing[$idx] = VmSnapshot::create([
                        'uuid' => $idx,
                        'moref' => $snapshot->snapshot->_,
                        'vcenter_uuid' => $vCenterUuid
                    ], $connection);
                }
                $existing[$idx]->setMapped($snapshot, $vCenter);
            }
        }

        $this->storeObjects($vCenter->getDb(), $existing, $seen);
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
