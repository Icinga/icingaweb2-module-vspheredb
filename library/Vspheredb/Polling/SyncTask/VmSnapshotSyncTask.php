<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmSnapshot;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmSnapshotPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmSnapshotSyncStore;

class VmSnapshotSyncTask extends SyncTask
{
    protected string $label = 'VM Snapshots';

    protected string $tableName = 'vm_snapshot';

    protected string $objectClass = VmSnapshot::class;

    protected string $selectSetClass = VirtualMachineSelectSet::class;

    protected string $propertySetClass = VmSnapshotPropertySet::class;

    protected string $syncStoreClass = VmSnapshotSyncStore::class;
}
