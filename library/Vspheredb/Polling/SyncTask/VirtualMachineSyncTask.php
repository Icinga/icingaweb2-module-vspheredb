<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Polling\PropertySet\VirtualMachinePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class VirtualMachineSyncTask extends SyncTask
{
    protected $label = 'Virtual Machines';
    protected $tableName = 'virtual_machine';
    protected $objectClass = VirtualMachine::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VirtualMachinePropertySet::class;
    protected $syncStoreClass = ObjectSyncStore::class;
}
