<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostSensorSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'runtime.healthSystemRuntime.systemHealthInfo.numericSensorInfo';
    protected $keyProperty = 'name';
    protected $dbKeyProperty = 'name';
    protected $instanceClass = 'HostNumericSensorInfo';
}
