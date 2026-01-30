<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

class HostHardwareSyncStore extends HostPropertyInstancesSyncStore
{
    protected string $baseKey = 'hardware.pciDevice';

    protected string $keyProperty = 'id';

    protected string $dbKeyProperty = 'id';

    protected string $instanceClass = 'HostPciDevice';
}
