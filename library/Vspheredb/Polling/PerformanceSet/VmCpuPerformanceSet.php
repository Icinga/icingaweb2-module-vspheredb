<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmCpuPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'VmCpu';
    protected $objectType = 'VirtualMachine';
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
