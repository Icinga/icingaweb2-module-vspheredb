<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;

class DatastoreUsageRuleDefinition extends MonitoringRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::DATASTORE,
    ];

    public static function getIdentifier(): string
    {
        return 'DatastoreUsage';
    }

    public function getLabel(): string
    {
        return $this->translate('Datastore Usage');
    }

    public function getInternalDefaults(): array
    {
        return [
            'threshold_precedence' => 'best_wins'
        ];
    }

    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        $this->assertSupportedObject($object);
        // (ds.uncommitted / ds.capacity) * 100 AS uncommitted_percent

        return [
            MemoryUsageHelper::prepareState($settings, $object->get('free_space'), $object->get('capacity'))
        ];
    }

    public function getParameters(): array
    {
        return MemoryUsageHelper::getParameters();
    }
}
