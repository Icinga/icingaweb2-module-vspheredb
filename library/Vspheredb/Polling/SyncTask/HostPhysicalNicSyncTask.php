<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostPhysicalNic;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostPhysicalNicPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;

class HostPhysicalNicSyncTask extends SyncTask
{
    protected $label = 'Host Physical NICs';
    protected $tableName = 'host_physical_nic';
    protected $objectClass = HostPhysicalNic::class;
    protected $selectSetClass = HostSystemSelectSet::class;
    protected $propertySetClass = HostPhysicalNicPropertySet::class;
}
