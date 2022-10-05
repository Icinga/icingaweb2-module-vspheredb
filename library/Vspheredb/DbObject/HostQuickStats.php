<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db;

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

    protected static $preloadCache = null;

    public static function preloadAll(Db $db)
    {
        self::$preloadCache = self::loadAll($db, null, 'uuid');
    }

    public static function clearPreloadCache()
    {
        self::$preloadCache = null;
    }

    public static function loadFor(HostSystem $object)
    {
        if ($object->hasBeenLoadedFromDb()) {
            $connection = $object->getConnection();
            $uuid = $object->get('uuid');
            if (self::$preloadCache === null) {
                if (static::exists($uuid, $connection)) {
                    return static::load($uuid, $connection);
                }
            } else {
                if (isset(self::$preloadCache[$uuid])) {
                    return self::$preloadCache[$uuid];
                }
            }
        }

        return static::create();
    }
}
