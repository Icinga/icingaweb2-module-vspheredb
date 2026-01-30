<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use Icinga\Module\Vspheredb\Polling\PropertySet\FullObjectListPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\FullSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ManagedObjectReferenceSyncStore;

class ManagedObjectReferenceSyncTask extends SyncTask
{
    protected string $label = 'Managed Object References';

    protected string $tableName = 'object';

    protected string $objectClass = ManagedObject::class;

    protected string $selectSetClass = FullSelectSet::class;

    protected string $propertySetClass = FullObjectListPropertySet::class;

    protected string $syncStoreClass = ManagedObjectReferenceSyncStore::class;
}
