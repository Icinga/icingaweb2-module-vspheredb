<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class HostMemoryPerformanceSet extends DefaultPerformanceSet
{
    protected ?string $name = 'HostMemory';

    protected ?string $objectType = 'HostSystem';

    protected ?string $countersGroup = 'mem';

    protected ?array $counters = [
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
