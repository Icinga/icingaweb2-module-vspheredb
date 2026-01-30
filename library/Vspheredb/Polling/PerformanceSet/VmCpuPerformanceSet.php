<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmCpuPerformanceSet extends DefaultPerformanceSet
{
    protected ?string $name = 'VmCpu';

    protected ?string $objectType = 'VirtualMachine';

    protected ?string $countersGroup = 'cpu';

    protected ?array $counters = [
        'coreUtilization',
        'demand',
        'latency',
        'readiness',
        'usage',
        'usagemhz',
        'utilization',
    ];
}
