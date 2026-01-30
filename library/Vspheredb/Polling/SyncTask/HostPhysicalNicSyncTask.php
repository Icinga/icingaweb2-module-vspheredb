<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostPhysicalNic;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostPhysicalNicPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostPhysicalNicSyncStore;

class HostPhysicalNicSyncTask extends SyncTask
{
    protected string $label = 'Host Physical NICs';

    protected string $tableName = 'host_physical_nic';

    protected string $objectClass = HostPhysicalNic::class;

    protected string $selectSetClass = HostSystemSelectSet::class;

    protected string $propertySetClass = HostPhysicalNicPropertySet::class;

    protected string $syncStoreClass = HostPhysicalNicSyncStore::class;
}
