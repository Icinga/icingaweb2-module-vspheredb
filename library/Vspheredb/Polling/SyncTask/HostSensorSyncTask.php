<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostSensor;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostSensorsPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostSensorSyncStore;

class HostSensorSyncTask extends SyncTask
{
    protected $label = 'Host Sensors';
    protected $tableName = 'host_sensor';
    protected $objectClass = HostSensor::class;
    protected $selectSetClass = HostSystemSelectSet::class;
    protected $propertySetClass = HostSensorsPropertySet::class;
    protected $syncStoreClass = HostSensorSyncStore::class;
}
