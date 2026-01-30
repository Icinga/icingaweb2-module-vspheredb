<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\Polling\PropertySet\DatastorePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\DatastoreSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class DatastoreSyncTask extends SyncTask
{
    protected string $label = 'Data Stores';

    protected string $tableName = 'datastore';

    protected string $objectClass = Datastore::class;

    protected string $selectSetClass = DatastoreSelectSet::class;

    protected string $propertySetClass = DatastorePropertySet::class;

    protected string $syncStoreClass = ObjectSyncStore::class;
}
