<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Db;

class VmQuickStats extends BaseDbObject
{
    protected string|array|null $keyName = 'uuid';

    protected ?string $table = 'vm_quick_stats';

    protected ?array $defaultProperties = [
        'uuid'                              => null,
        'ballooned_memory_mb'               => null,
        'compressed_memory_kb'              => null,
        'consumed_overhead_memory_mb'       => null,
        'distributed_cpu_entitlement'       => null,
        'distributed_memory_entitlement_mb' => null,
        'ft_latency_status'                 => null,
        'ft_log_bandwidth'                  => null,
        'ft_secondary_latency'              => null,
        'guest_heartbeat_status'            => null,
        'guest_memory_usage_mb'             => null,
        'host_memory_usage_mb'              => null,
        'overall_cpu_demand'                => null,
        'overall_cpu_usage'                 => null,
        'private_memory_mb'                 => null,
        'shared_memory_mb'                  => null,
        'ssd_swapped_memory_kb'             => null,
        'static_cpu_entitlement'            => null,
        'static_memory_entitlement_mb'      => null,
        'swapped_memory_mb'                 => null,
        'uptime'                            => null,
        'vcenter_uuid'                      => null
    ];

    protected array $propertyMap = [
        'summary.quickStats.balloonedMemory'              => 'ballooned_memory_mb',
        'summary.quickStats.compressedMemory'             => 'compressed_memory_kb',
        'summary.quickStats.consumedOverheadMemory'       => 'consumed_overhead_memory_mb',
        'summary.quickStats.distributedCpuEntitlement'    => 'distributed_cpu_entitlement',
        'summary.quickStats.distributedMemoryEntitlement' => 'distributed_memory_entitlement_mb',
        'summary.quickStats.ftLatencyStatus'              => 'ft_latency_status',
        'summary.quickStats.ftLogBandwidth'               => 'ft_log_bandwidth',
        'summary.quickStats.ftSecondaryLatency'           => 'ft_secondary_latency',
        'summary.quickStats.guestHeartbeatStatus'         => 'guest_heartbeat_status',
        'summary.quickStats.guestMemoryUsage'             => 'guest_memory_usage_mb',
        'summary.quickStats.hostMemoryUsage'              => 'host_memory_usage_mb',
        'summary.quickStats.overallCpuDemand'             => 'overall_cpu_demand',
        'summary.quickStats.overallCpuUsage'              => 'overall_cpu_usage',
        'summary.quickStats.privateMemory'                => 'private_memory_mb',
        'summary.quickStats.sharedMemory'                 => 'shared_memory_mb',
        'summary.quickStats.ssdSwappedMemory'             => 'ssd_swapped_memory_kb',
        'summary.quickStats.staticCpuEntitlement'         => 'static_cpu_entitlement',
        'summary.quickStats.staticMemoryEntitlement'      => 'static_memory_entitlement_mb',
        'summary.quickStats.swappedMemory'                => 'swapped_memory_mb',
        'summary.quickStats.uptimeSeconds'                => 'uptime'
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
     * Valid are values from 0 to max allowed memory, but I've met -1 on an
     * ESXi host in the wild (6.7)
     *
     * @param int $value
     *
     * @return static
     */
    public function setHost_memory_usage_mb(int $value): static // phpcs:ignore
    {
        if ($value === -1) {
            $value = null;
        }

        parent::reallySet('host_memory_usage_mb', $value);

        return $this;
    }

    /**
     * @param VirtualMachine $object
     *
     * @return VmQuickStats|mixed|static
     */
    public static function loadFor(VirtualMachine $object): mixed
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

    /**
     * @param int $value
     *
     * @return VmQuickStats
     */
    protected function setUptime(int $value): VmQuickStats
    {
        if ($value === 0) {
            $value = null;
        }

        return parent::reallySet('uptime', $value);
    }
}
