<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostSystemPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\ObjectSyncStore;

class HostSystemSyncTask extends SyncTask
{
    protected string $label = 'Host Systems';

    protected string $tableName = 'host_system';

    protected string $objectClass = HostSystem::class;

    protected string $selectSetClass = HostSystemSelectSet::class;

    protected string $propertySetClass = HostSystemPropertySet::class;

    protected string $syncStoreClass = ObjectSyncStore::class;
}
