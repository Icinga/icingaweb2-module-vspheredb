<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostVirtualNicSyncStore extends HostPropertyInstancesSyncStore
{
    protected string $baseKey = 'config.network.vnic';

    protected string $keyProperty = 'key';

    protected string $dbKeyProperty = 'nic_key';

    protected string $instanceClass = 'HostVirtualNic';
}
