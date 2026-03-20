<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmDiskUsage;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmDiskUsagePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmDiskUsageSyncStore;

class VmDiskUsageSyncTask extends SyncTask
{
    protected string $label = 'VM Disk Usage';

    protected string $tableName = 'vm_disk_usage';

    protected string $objectClass = VmDiskUsage::class;

    protected string $selectSetClass = VirtualMachineSelectSet::class;

    protected string $propertySetClass = VmDiskUsagePropertySet::class;

    protected string $syncStoreClass = VmDiskUsageSyncStore::class;
}
