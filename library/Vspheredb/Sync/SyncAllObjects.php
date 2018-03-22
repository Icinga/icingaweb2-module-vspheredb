<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\DistributedVirtualPortgroup;
use Icinga\Module\Vspheredb\DbObject\DistributedVirtualSwitch;
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
        Logger::debug('Refreshing objects');
        (new SyncManagedObjectReferences($vCenter))->sync();
        Logger::debug('Refreshed objects');
        HostSystem::syncFromApi($vCenter);
        VirtualMachine::syncFromApi($vCenter);
        Datastore::syncFromApi($vCenter);
        DistributedVirtualPortgroup::syncFromApi($vCenter);
        DistributedVirtualSwitch::syncFromApi($vCenter);

        return $this;
    }
}
