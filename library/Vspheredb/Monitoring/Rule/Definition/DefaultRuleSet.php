<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

class DefaultRuleSet extends MonitoringRuleSetDefinition
{
    public const RULE_CLASSES = [
        VMwareObjectStateRuleDefinition::class,
        PowerStateRuleDefinition::class,
    ];

    public function getLabel(): string
    {
        return $this->translate('Default Rules');
    }

    public static function getIdentifier(): string
    {
        return 'Default';
    }
}
