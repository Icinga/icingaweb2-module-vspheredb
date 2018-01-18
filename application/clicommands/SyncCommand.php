<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\Sync\Sync;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounterInfo;
use Icinga\Module\Vspheredb\Sync\SyncPerfCounters;

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
     * icingacli vsphere sync objects --server_id <id>
     */
    public function objectsAction()
    {
        $sync = new Sync($this->api(), $this->db());
        $sync->syncAllObjects();
    }

    public function perfcountersAction()
    {
        $sync = new SyncPerfCounters($this->api(), $this->db());
        $sync->run();
    }

    public function perfcounterinfoAction()
    {
        $sync = new SyncPerfCounterInfo($this->api(), $this->db());
        $sync->run();
    }
}
