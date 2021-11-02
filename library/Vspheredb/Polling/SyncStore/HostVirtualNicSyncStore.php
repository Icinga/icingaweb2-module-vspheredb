<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostVirtualNicSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'config.network.vnic';
    protected $keyProperty = 'key';
    protected $dbKeyProperty = 'nic_key';
    protected $instanceClass = 'HostVirtualNic';
}
