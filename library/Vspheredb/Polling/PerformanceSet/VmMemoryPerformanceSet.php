<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmMemoryPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'VmMemory';
    protected $objectType = 'VirtualMachine';
    protected $countersGroup = 'mem';
    protected $counters = [
        'active',
        'usage',
        'granted',
        'latency',
        'swapin',
        'swapout',
        'swapinRate',
        'swapoutRate',
        'vmmemctl',
    ];
}
