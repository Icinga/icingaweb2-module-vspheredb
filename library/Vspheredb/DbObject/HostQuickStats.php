<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db;

class HostQuickStats extends BaseDbObject
{
    protected string|array|null $keyName = 'uuid';

    protected ?string $table = 'host_quick_stats';

    protected ?array $defaultProperties = [
        'uuid'                        => null,
        'distributed_cpu_fairness'    => null,
        'distributed_memory_fairness' => null,
        'overall_cpu_usage'           => null,
        'overall_memory_usage_mb'     => null,
        'uptime'                      => null,
        'vcenter_uuid'                => null
    ];

    protected array $propertyMap = [
        'summary.quickStats.distributedCpuFairness'    => 'distributed_cpu_fairness',
        'summary.quickStats.distributedMemoryFairness' => 'distributed_memory_fairness',
        'summary.quickStats.overallCpuUsage'           => 'overall_cpu_usage',
        'summary.quickStats.overallMemoryUsage'        => 'overall_memory_usage_mb',
        'summary.quickStats.uptime'                    => 'uptime'
    ];

    /** @var ?static[] */
    protected static ?array $preloadCache = null;

    /**
     * @param Db $db
     *
     * @return void
     */
    public static function preloadAll(Db $db): void
    {
        self::$preloadCache = self::loadAll($db, null, 'uuid');
    }

    /**
     * @return void
     */
    public static function clearPreloadCache(): void
    {
        self::$preloadCache = null;
    }

    /**
     * @param HostSystem $object
     *
     * @return static
     */
    public static function loadFor(HostSystem $object): static
    {
        if ($object->hasBeenLoadedFromDb()) {
            $connection = $object->getConnection();
            /** @var string $uuid */
            $uuid = $object->get('uuid') ?? '';
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
