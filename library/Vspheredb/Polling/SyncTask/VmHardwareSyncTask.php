<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmHardware;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmHardwarePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmHardwareSyncStore;

class VmHardwareSyncTask extends SyncTask
{
    protected $label = 'VM Hardware';
    protected $tableName = 'vm_hardware';
    protected $objectClass = VmHardware::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VmHardwarePropertySet::class;
    protected $syncStoreClass = VmHardwareSyncStore::class;
}
