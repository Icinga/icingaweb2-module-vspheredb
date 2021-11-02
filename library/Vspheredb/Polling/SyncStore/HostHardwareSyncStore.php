<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostHardwareSyncStore extends HostPropertyInstancesSyncStore
{
    protected $baseKey = 'hardware.pciDevice';
    protected $keyProperty = 'id';
    protected $dbKeyProperty = 'id';
    protected $instanceClass = 'HostPciDevice';
}
