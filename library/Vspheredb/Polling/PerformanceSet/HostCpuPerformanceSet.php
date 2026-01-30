<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class HostCpuPerformanceSet extends DefaultPerformanceSet
{
    protected ?string $name = 'HostCpu';

    protected ?string $objectType = 'HostSystem';

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
