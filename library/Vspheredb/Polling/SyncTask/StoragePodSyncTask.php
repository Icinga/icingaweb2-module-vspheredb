<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\StoragePod;
use Icinga\Module\Vspheredb\Polling\PropertySet\StoragePodPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\StoragePodSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class StoragePodSyncTask extends SyncTask
{
    protected string $label = 'Storage Pods';

    protected string $tableName = 'storage_pod';

    protected string $objectClass = StoragePod::class;

    protected string $selectSetClass = StoragePodSelectSet::class;

    protected string $propertySetClass = StoragePodPropertySet::class;

    protected string $syncStoreClass = ObjectSyncStore::class;
}
