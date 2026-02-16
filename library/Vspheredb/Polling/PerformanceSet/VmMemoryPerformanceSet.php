<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmMemoryPerformanceSet extends DefaultPerformanceSet
{
    protected ?string $name = 'VmMemory';

    protected ?string $objectType = 'VirtualMachine';

    protected ?string $countersGroup = 'mem';

    protected ?array $counters = [
        'active',
        'usage',
        'granted',
        'latency',
        'swapin',
        'swapout',
        'swapinRate',
        'swapoutRate',
        'vmmemctl'
    ];
}
