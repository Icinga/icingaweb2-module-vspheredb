<?php

namespace Icinga\Module\Vspheredb\DbObject;

class ClusterComputeResourceQuickStats extends BaseDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'cl_compute_resource_quick_stats';

    protected $defaultProperties = [
        'uuid'                => null,
        'cpu_capacity_mhz'    => null,
        'cp_usage_mhz'        => null,
        'memory_capacity_mb'  => null,
        'memory_used_mb'      => null,
        'storage_capacity_mb' => null,
        'storage_used_mb'     => null,
        'vcenter_uuid'        => null,
    ];

    protected $propertyMap = [
        // Sum of CPU capacity of all the available hosts in the cluster in MHz
        'summary.quickStats.cpuCapacityMHz'    => 'cpu_capacity_mhz',
        // Sum of CPU consumed in all the available hosts in the cluster in MHz
        'summary.quickStats.cpuUsedMHz'        => 'cp_usage_mhz',
        // Sum of memory capacity of all the available hosts in the cluster in MB
        'summary.quickStats.memCapacityMB'     => 'memory_capacity_mb',
        // Sum of memory consumed in all the available hosts in this cluster in MB.
        'summary.quickStats.memUsedMB'         => 'memory_used_mb',
        // Total storage capacity of all the accessible datastores in this cluster.
        'summary.quickStats.storageCapacityMB' => 'storage_capacity_mb',
        // Total storage consumed in all the accessible datastores in this cluster.
        'summary.quickStats.storageUsedMB'     => 'storage_used_mb',
    ];

    public static function getType()
    {
        return ComputeCluster::getType();
    }

    public static function getSelectSet()
    {
        return ComputeCluster::getSelectSet();
    }
}
