<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;

class ActiveMemoryUsageRuleDefinition extends MemoryUsageRuleDefinition
{
    public const SUPPORTED_OBJECT_TYPES = [
        ObjectType::VIRTUAL_MACHINE,
    ];

    public static function getIdentifier(): string
    {
        return 'ActiveMemoryUsage';
    }

    public function getLabel(): string
    {
        return $this->translate('Active Memory Usage');
    }

    protected function getUsedMemory(BaseDbObject $quickStats)
    {
        return $quickStats->get('guest_memory_usage_mb') * MemoryUsageHelper::MEGA_BYTE;
    }
}
