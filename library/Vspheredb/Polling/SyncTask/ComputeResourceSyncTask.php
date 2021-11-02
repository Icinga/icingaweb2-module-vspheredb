<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\ComputeResource;
use Icinga\Module\Vspheredb\Polling\PropertySet\ComputeResourcePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\ComputeResourceSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class ComputeResourceSyncTask extends SyncTask
{
    protected $label = 'Compute Resources';
    protected $tableName = 'compute_resource';
    protected $objectClass = ComputeResource::class;
    protected $selectSetClass = ComputeResourceSelectSet::class;
    protected $propertySetClass = ComputeResourcePropertySet::class;
    protected $syncStoreClass = ObjectSyncStore::class;
}
