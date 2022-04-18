<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

class ConfigurationPolicyRuleSet extends MonitoringRuleSetDefinition
{
    public const RULE_CLASSES = [
        GuestUtilitiesRuleDefinition::class,
    ];

    public function getLabel(): string
    {
        return $this->translate('Configuration Policy');
    }

    public static function getIdentifier(): string
    {
        return 'ConfigurationPolicy';
    }
}
