<?php

namespace Icinga\Module\Vspheredb\DbObject;

class HostQuickStats extends BaseDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'host_quick_stats';

    protected $defaultProperties = [
        'uuid'                        => null,
        'distributed_cpu_fairness'    => null,
        'distributed_memory_fairness' => null,
        'overall_cpu_usage'           => null,
        'overall_memory_usage_mb'     => null,
        'uptime'                      => null,
        'vcenter_uuid'                => null,
    ];

    protected $propertyMap = [
        'summary.quickStats.distributedCpuFairness'    => 'distributed_cpu_fairness',
        'summary.quickStats.distributedMemoryFairness' => 'distributed_memory_fairness',
        'summary.quickStats.overallCpuUsage'           => 'overall_cpu_usage',
        'summary.quickStats.overallMemoryUsage'        => 'overall_memory_usage_mb',
        'summary.quickStats.uptime'                    => 'uptime',
    ];

    public static function loadFor(HostSystem $object)
    {
        if ($object->hasBeenLoadedFromDb()) {
            $connection = $object->getConnection();
            $uuid = $object->get('uuid');
            if (static::exists($uuid, $connection)) {
                return static::load($uuid, $connection);
            }
        }

        return static::create();
    }
}
