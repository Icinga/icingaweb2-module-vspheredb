<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmQuickStatsPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class VmQuickStatsSyncTask extends SyncTask
{
    protected string $label = 'VM Quick Stats';

    protected string $tableName = 'vm_quick_stats';

    protected string $objectClass = VmQuickStats::class;

    protected string $selectSetClass = VirtualMachineSelectSet::class;

    protected string $propertySetClass = VmQuickStatsPropertySet::class;

    protected string $syncStoreClass = ObjectSyncStore::class;
}
