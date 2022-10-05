<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

class DatastoreHealthRuleSet extends MonitoringRuleSetDefinition
{
    public const RULE_CLASSES = [
        DatastoreUsageRuleDefinition::class,
    ];

    public function getLabel(): string
    {
        return $this->translate('Datastore Health');
    }

    public static function getIdentifier(): string
    {
        return 'DatastoreHealth';
    }
}
