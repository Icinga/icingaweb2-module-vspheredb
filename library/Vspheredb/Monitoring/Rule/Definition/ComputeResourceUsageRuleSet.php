<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

class ComputeResourceUsageRuleSet extends MonitoringRuleSetDefinition
{
    public const RULE_CLASSES = [
        CpuUsageRuleDefinition::class,
        MemoryUsageRuleDefinition::class,
        ActiveMemoryUsageRuleDefinition::class,
    ];

    public function getLabel(): string
    {
        return $this->translate('Compute Resource Usage');
    }

    public static function getIdentifier(): string
    {
        return 'ComputeResourceUsage';
    }
}
