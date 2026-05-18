<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Polling\PropertySet\VirtualMachinePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class VirtualMachineSyncTask extends SyncTask
{
    protected string $label = 'Virtual Machines';

    protected string $tableName = 'virtual_machine';

    protected string $objectClass = VirtualMachine::class;

    protected string $selectSetClass = VirtualMachineSelectSet::class;

    protected string $propertySetClass = VirtualMachinePropertySet::class;

    protected string $syncStoreClass = ObjectSyncStore::class;
}
