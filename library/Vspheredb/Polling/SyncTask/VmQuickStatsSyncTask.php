<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmQuickStatsPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class VmQuickStatsSyncTask extends SyncTask
{
    protected $label = 'VM Quick Stats';
    protected $tableName = 'vm_quick_stats';
    protected $objectClass = VmQuickStats::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VmQuickStatsPropertySet::class;
    protected $syncStoreClass = ObjectSyncStore::class;
}
