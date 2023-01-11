<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class HostMemoryPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'HostMemory';
    protected $objectType = 'HostSystem';
    protected $countersGroup = 'mem';
    protected $counters = [
        'active',
        'usage',
        'totalCapacity',
        'latency',
        'swapin',
        'swapout',
        'swapinRate',
        'swapoutRate',
        'vmmemctl',
    ];
}
