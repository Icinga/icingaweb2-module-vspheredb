<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostVirtualNic;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostVirtualNicPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostVirtualNicSyncStore;

class HostVirtualNicSyncTask extends SyncTask
{
    protected string $label = 'Host Virtual NICs';

    protected string $tableName = 'host_virtual_nic';

    protected string $objectClass = HostVirtualNic::class;

    protected string $selectSetClass = HostSystemSelectSet::class;

    protected string $propertySetClass = HostVirtualNicPropertySet::class;

    protected string $syncStoreClass = HostVirtualNicSyncStore::class;
}
