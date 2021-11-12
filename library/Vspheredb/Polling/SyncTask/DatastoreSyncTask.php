<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\Polling\PropertySet\DatastorePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\DatastoreSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class DatastoreSyncTask extends SyncTask
{
    protected $label = 'Data Stores';
    protected $tableName = 'datastore';
    protected $objectClass = Datastore::class;
    protected $selectSetClass = DatastoreSelectSet::class;
    protected $propertySetClass = DatastorePropertySet::class;
    protected $syncStoreClass = ObjectSyncStore::class;
}
