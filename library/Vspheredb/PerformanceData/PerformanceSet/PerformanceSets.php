<?php

namespace Icinga\Module\Vspheredb\PerformanceData\PerformanceSet;

class PerformanceSets
{
    public static function listAvailableSets()
    {
        return [
            VmDisks::class   => mt('Virtual Disks (I/O, Seeks, Latency)'),
            VmNetwork::class => mt('Virtual Network Adapters (Octets, Packets)'),
        ];
    }
}
