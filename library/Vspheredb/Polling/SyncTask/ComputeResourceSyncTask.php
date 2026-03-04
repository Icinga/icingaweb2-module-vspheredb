<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\ComputeResource;
use Icinga\Module\Vspheredb\Polling\PropertySet\ComputeResourcePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\ComputeResourceSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class ComputeResourceSyncTask extends SyncTask
{
    protected string $label = 'Compute Resources';

    protected string $tableName = 'compute_resource';

    protected string $objectClass = ComputeResource::class;

    protected string $selectSetClass = ComputeResourceSelectSet::class;

    protected string $propertySetClass = ComputeResourcePropertySet::class;

    protected string $syncStoreClass = ObjectSyncStore::class;
}
