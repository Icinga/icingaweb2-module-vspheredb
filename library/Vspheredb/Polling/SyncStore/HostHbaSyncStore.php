<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostHbaSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'config.storageDevice.hostBusAdapter';
    protected $keyProperty = 'key';
    protected $dbKeyProperty = 'hba_key';
    protected $instanceClass = 'HostHostBusAdapter';
}
