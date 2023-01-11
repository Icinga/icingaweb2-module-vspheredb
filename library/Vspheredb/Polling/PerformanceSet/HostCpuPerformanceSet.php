<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class HostCpuPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'HostCpu';
    protected $objectType = 'HostSystem';
    protected $countersGroup = 'cpu';
    protected $counters = [
        'coreUtilization',
        'demand',
        'latency',
        'readiness',
        'usage',
        'usagemhz',
        'utilization',
    ];
}
