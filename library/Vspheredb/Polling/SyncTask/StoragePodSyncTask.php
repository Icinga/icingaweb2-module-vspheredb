<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\StoragePod;
use Icinga\Module\Vspheredb\Polling\PropertySet\StoragePodPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\StoragePodSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class StoragePodSyncTask extends SyncTask
{
    protected $label = 'Storage Pods';
    protected $tableName = 'storage_pod';
    protected $objectClass = StoragePod::class;
    protected $selectSetClass = StoragePodSelectSet::class;
    protected $propertySetClass = StoragePodPropertySet::class;
    protected $syncStoreClass = ObjectSyncStore::class;
}
