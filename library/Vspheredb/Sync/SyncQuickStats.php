<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;

class SyncQuickStats
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
        Logger::debug('Ready to sync QuickStats for Hosts and VMs');
        HostQuickStats::syncFromApi($vCenter);
        VmQuickStats::syncFromApi($vCenter);

        return $this;
    }
}
