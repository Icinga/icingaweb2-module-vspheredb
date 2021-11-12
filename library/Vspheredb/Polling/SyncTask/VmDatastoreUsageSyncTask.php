<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\VmDatastoreUsage;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmDatastoreUsagePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmDatastoreUsageSyncStore;

class VmDatastoreUsageSyncTask extends SyncTask
{
    // TODO: refresh logic! -> pick outdated ones, trigger refresh
    protected $label = 'VM Datastore Usage';
    protected $tableName = 'vm_datastore_usage';
    protected $objectClass = VmDatastoreUsage::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VmDatastoreUsagePropertySet::class;
    protected $syncStoreClass = VmDatastoreUsageSyncStore::class;
}
