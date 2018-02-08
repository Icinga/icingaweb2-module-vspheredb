<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\Sync\SyncAllObjects;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounterInfo;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounters;
use Icinga\Module\Vspheredb\Sync\SyncVmDatastoreUsage;
use Icinga\Module\Vspheredb\Sync\SyncVmHardware;

/**
 * Sync a vCenter or ESXi host
 */
class SyncCommand extends CommandBase
{
    /**
     * Sync all objects
     *
     * Still a prototype
     *
     * USAGE
     *
     * icingacli vsphere sync objects --vCenter <id>
     */
    public function objectsAction()
    {
        $sync = new SyncAllObjects($this->getVCenter());
        $sync->run();
    }

    public function perfcountersAction()
    {
        $sync = new SyncPerfCounters($this->getVCenter());
        $sync->run();
    }

    public function perfcounterinfoAction()
    {
        $sync = new SyncPerfCounterInfo($this->getVCenter());
        $sync->run();
    }

    public function vmhardwareAction()
    {
        $sync = new SyncVmHardware($this->getVCenter());
        $sync->run();
    }

    public function vmdatastoreusageAction()
    {
        $sync = new SyncVmDatastoreUsage($this->getVCenter());
        $sync->run();
    }
}