<?php

namespace Icinga\Module\Vspheredb\Sync;

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
        HostQuickStats::syncFromApi($vCenter);
        VmQuickStats::syncFromApi($vCenter);

        return $this;
    }
}
