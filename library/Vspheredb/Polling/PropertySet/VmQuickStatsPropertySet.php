<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

class VmQuickStatsPropertySet implements PropertySet
{
    public static function create()
    {
        return [
            PropertySpec::create('VirtualMachine', [
                'summary.quickStats.balloonedMemory',
                'summary.quickStats.compressedMemory',
                'summary.quickStats.consumedOverheadMemory',
                'summary.quickStats.distributedCpuEntitlement',
                'summary.quickStats.distributedMemoryEntitlement',
                'summary.quickStats.ftLatencyStatus',
                'summary.quickStats.ftLogBandwidth',
                'summary.quickStats.ftSecondaryLatency',
                'summary.quickStats.guestHeartbeatStatus',
                'summary.quickStats.guestMemoryUsage',
                'summary.quickStats.hostMemoryUsage',
                'summary.quickStats.overallCpuDemand',
                'summary.quickStats.overallCpuUsage',
                'summary.quickStats.privateMemory',
                'summary.quickStats.sharedMemory',
                'summary.quickStats.ssdSwappedMemory',
                'summary.quickStats.staticCpuEntitlement',
                'summary.quickStats.staticMemoryEntitlement',
                'summary.quickStats.swappedMemory',
                'summary.quickStats.uptimeSeconds',
            ])
        ];
    }
}
