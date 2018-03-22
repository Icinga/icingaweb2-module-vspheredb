<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\Sync\SyncAllObjects;
use Icinga\Module\Vspheredb\Sync\SyncHostHardware;
use Icinga\Module\Vspheredb\Sync\SyncHostSensors;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounterInfo;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounters;
use Icinga\Module\Vspheredb\Sync\SyncQuickStats;
use Icinga\Module\Vspheredb\Sync\SyncVmDatastoreUsage;
use Icinga\Module\Vspheredb\Sync\SyncVmDiskUsage;
use Icinga\Module\Vspheredb\Sync\SyncVmHardware;
use Icinga\Module\Vspheredb\Sync\SyncVmSnapshots;

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

    public function quickstatsAction()
    {
        $sync = new SyncQuickStats($this->getVCenter());
        $sync->run();
    }

    public function vmhardwareAction()
    {
        $sync = new SyncVmHardware($this->getVCenter());
        $sync->run();
    }

    public function hosthardwareAction()
    {
        $sync = new SyncHostHardware($this->getVCenter());
        $sync->run();
    }

    public function hostsensorsAction()
    {
        $sync = new SyncHostSensors($this->getVCenter());
        $sync->run();
    }

    public function vmdiskusageAction()
    {
        $sync = new SyncVmDiskUsage($this->getVCenter());
        $sync->run();
    }

    public function vmsnapshotAction()
    {
        $sync = new SyncVmSnapshots($this->getVCenter());
        $sync->run();
    }

    public function vmdatastoreusageAction()
    {
        $sync = new SyncVmDatastoreUsage($this->getVCenter());
        $sync->run();
    }
}
