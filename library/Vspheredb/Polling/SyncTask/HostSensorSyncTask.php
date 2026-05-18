<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\DbObject\HostSensor;
use Icinga\Module\Vspheredb\Polling\PropertySet\HostSensorsPropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\HostSystemSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\HostSensorSyncStore;

class HostSensorSyncTask extends SyncTask
{
    protected string $label = 'Host Sensors';

    protected string $tableName = 'host_sensor';

    protected string $objectClass = HostSensor::class;

    protected string $selectSetClass = HostSystemSelectSet::class;

    protected string $propertySetClass = HostSensorsPropertySet::class;

    protected string $syncStoreClass = HostSensorSyncStore::class;
}
