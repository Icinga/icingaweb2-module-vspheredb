<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmDisk;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmDatastoreUsagePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;

class VmDatastoreUsageSyncTask extends SyncTask
{
    // TODO: refresh logic! -> pick outdated ones, trigger refresh
    protected $label = 'VM Datastore Usage';
    protected $tableName = 'vm_disk';
    protected $objectClass = VmDisk::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VmDatastoreUsagePropertySet::class;
}
