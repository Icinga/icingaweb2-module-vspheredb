<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;

class MemoryUsageRuleDefinition extends MonitoringRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::HOST_SYSTEM,
        ObjectType::VIRTUAL_MACHINE,
    ];

    public static function getIdentifier(): string
    {
        return 'MemoryUsage';
    }

    public function getLabel(): string
    {
        return $this->translate('Memory Usage');
    }

    public function getInternalDefaults(): array
    {
        return [
            'threshold_precedence' => 'best_wins'
        ];
    }

    protected function getUsedMemory(BaseDbObject $quickStats)
    {
        if ($quickStats instanceof VmQuickStats) {
            return $quickStats->get('host_memory_usage_mb') * MemoryUsageHelper::MEGA_BYTE;
        }

        return $quickStats->get('overall_memory_usage_mb') * MemoryUsageHelper::MEGA_BYTE;
    }

    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        $this->assertSupportedObject($object);
        if ($object instanceof HostSystem) {
            $quickStats = HostQuickStats::loadFor($object);
            $capacity = $object->get('hardware_memory_size_mb') * MemoryUsageHelper::MEGA_BYTE;
        } elseif ($object instanceof VirtualMachine) {
            $quickStats = VmQuickStats::loadFor($object);
            $capacity = $object->get('hardware_memorymb') * MemoryUsageHelper::MEGA_BYTE;
        } else {
            throw new \InvalidArgumentException('Cannot load QuickStats for ' . get_class($object));
        }
        $used = $this->getUsedMemory($quickStats);
        $free = $capacity - $used;
        return [
            MemoryUsageHelper::prepareState($settings, $free, $capacity)
        ];
    }

    public function getParameters(): array
    {
        return MemoryUsageHelper::getParameters();
    }
}
