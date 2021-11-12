<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostSystemPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class HostSystemSyncTask extends SyncTask
{
    protected $label = 'Host Systems';
    protected $tableName = 'host_system';
    protected $objectClass = HostSystem::class;
    protected $selectSetClass = HostSystemSelectSet::class;
    protected $propertySetClass = HostSystemPropertySet::class;
    protected $syncStoreClass = ObjectSyncStore::class;
}
