<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostHba;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostHbaPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostHbaSyncStore;

class HostHbaSyncTask extends SyncTask
{
    protected string $label = 'Host HBAs';

    protected string $tableName = 'host_hba';

    protected string $objectClass = HostHba::class;

    protected string $selectSetClass = HostSystemSelectSet::class;

    protected string $propertySetClass = HostHbaPropertySet::class;

    protected string $syncStoreClass = HostHbaSyncStore::class;
}
