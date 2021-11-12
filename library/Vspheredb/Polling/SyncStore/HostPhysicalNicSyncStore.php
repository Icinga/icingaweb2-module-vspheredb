<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostPhysicalNicSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'config.network.pnic';
    protected $keyProperty = 'key';
    protected $dbKeyProperty = 'nic_key';
    protected $instanceClass = 'PhysicalNic';
}
