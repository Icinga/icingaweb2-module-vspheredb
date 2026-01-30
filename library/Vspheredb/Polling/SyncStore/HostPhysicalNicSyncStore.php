<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostPhysicalNicSyncStore extends HostPropertyInstancesSyncStore
{
    protected string $baseKey = 'config.network.pnic';

    protected string $keyProperty = 'key';

    protected string $dbKeyProperty = 'nic_key';

    protected string $instanceClass = 'PhysicalNic';
}
