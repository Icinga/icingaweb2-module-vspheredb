<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostSensorSyncStore extends HostPropertyInstancesSyncStore
{
    protected string $baseKey = 'runtime.healthSystemRuntime.systemHealthInfo.numericSensorInfo';

    protected string $keyProperty = 'name';

    protected string $dbKeyProperty = 'name';

    protected string $instanceClass = 'HostNumericSensorInfo';
}
