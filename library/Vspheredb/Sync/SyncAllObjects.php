<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Benchmark;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;

class SyncAllObjects
{
    /** @var VCenter */
    protected $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

    public function run()
    {
        $vCenter = $this->vCenter;
        Benchmark::measure('Refreshing objects');
        (new SyncManagedObjectReferences($vCenter))->sync();
        Benchmark::measure('Refreshed objects');
        HostSystem::syncFromApi($vCenter);
        VirtualMachine::syncFromApi($vCenter);
        Datastore::syncFromApi($vCenter);

        return $this;
    }
}
