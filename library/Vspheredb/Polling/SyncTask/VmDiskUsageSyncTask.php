<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmDiskUsage;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmDiskUsagePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;

class VmDiskUsageSyncTask extends SyncTask
{
    protected $label = 'VM Disk Usage';
    protected $tableName = 'vm_disk_usage';
    protected $objectClass = VmDiskUsage::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VmDiskUsagePropertySet::class;
}
