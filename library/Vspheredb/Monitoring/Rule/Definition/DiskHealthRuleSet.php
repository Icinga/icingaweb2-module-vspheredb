<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

class DiskHealthRuleSet extends MonitoringRuleSetDefinition
{
    public const RULE_CLASSES = [
        SnapshotsRuleDefinition::class,
        DiskUsageRuleDefinition::class,
    ];

    public function getLabel(): string
    {
        return $this->translate('Disk Health');
    }

    public static function getIdentifier(): string
    {
        return 'DiskHealth';
    }
}
