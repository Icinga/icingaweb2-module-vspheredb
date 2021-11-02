<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmSnapshot;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmSnapshotPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;

class VmSnapshotSyncTask extends SyncTask
{
    protected $label = 'VM Snapshots';
    protected $tableName = 'vm_snapshot';
    protected $objectClass = VmSnapshot::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VmSnapshotPropertySet::class;
}
