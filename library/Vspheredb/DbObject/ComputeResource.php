<?php

namespace Icinga\Module\Vspheredb\DbObject;

class ComputeResource extends BaseDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'compute_resource';

    protected $defaultProperties = [
        'uuid'                     => null,
        'vcenter_uuid'             => null,
        'effective_cpu_mhz'        => null,
        'effective_memory_size_mb' => null,
        'cpu_cores'                => null,
        'cpu_threads'              => null,
        'effective_hosts'          => null,
        'hosts'                    => null,
        'total_cpu_mhz'            => null,
        'total_memory_size_mb'     => null,
    ];

    protected $propertyMap = [
        'summary.effectiveCpu'      => 'effective_cpu_mhz',
        'summary.effectiveMemory'   => 'effective_memory_size_mb',
        'summary.numCpuCores'       => 'cpu_cores',
        'summary.numCpuThreads'     => 'cpu_threads',
        'summary.numEffectiveHosts' => 'effective_hosts',
        'summary.numHosts'          => 'hosts',
        // 'summary.overallStatus' => '',
        'summary.totalCpu'          => 'total_cpu_mhz',
        'summary.totalMemory'       => 'total_memory_size_mb',
    ];
}
