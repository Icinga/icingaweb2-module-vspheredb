<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmDatastoreUsage;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmDatastoreUsagePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmDatastoreUsageSyncStore;

class VmDatastoreUsageSyncTask extends SyncTask
{
    // TODO: refresh logic! -> pick outdated ones, trigger refresh
    protected string $label = 'VM Datastore Usage';

    protected string $tableName = 'vm_datastore_usage';

    protected string $objectClass = VmDatastoreUsage::class;

    protected string $selectSetClass = VirtualMachineSelectSet::class;

    protected string $propertySetClass = VmDatastoreUsagePropertySet::class;

    protected string $syncStoreClass = VmDatastoreUsageSyncStore::class;
}
