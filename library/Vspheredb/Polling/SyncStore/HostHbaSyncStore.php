<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostHbaSyncStore extends HostPropertyInstancesSyncStore
{
    protected string $baseKey = 'config.storageDevice.hostBusAdapter';

    protected string $keyProperty = 'key';

    protected string $dbKeyProperty = 'hba_key';

    protected string $instanceClass = 'HostHostBusAdapter';
}
