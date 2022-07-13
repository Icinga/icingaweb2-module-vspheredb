<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostHba;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostHbaPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostHbaSyncStore;

class HostHbaSyncTask extends SyncTask
{
    protected $label = 'Host HBAs';
    protected $tableName = 'host_hba';
    protected $objectClass = HostHba::class;
    protected $selectSetClass = HostSystemSelectSet::class;
    protected $propertySetClass = HostHbaPropertySet::class;
    protected $syncStoreClass = HostHbaSyncStore::class;
}
