<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostVirtualNic;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostVirtualNicPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostVirtualNicSyncStore;

class HostVirtualNicSyncTask extends SyncTask
{
    protected $label = 'Host Virtual NICs';
    protected $tableName = 'host_Virtual_nic';
    protected $objectClass = HostVirtualNic::class;
    protected $selectSetClass = HostSystemSelectSet::class;
    protected $propertySetClass = HostVirtualNicPropertySet::class;
    protected $syncStoreClass = HostVirtualNicSyncStore::class;
}
