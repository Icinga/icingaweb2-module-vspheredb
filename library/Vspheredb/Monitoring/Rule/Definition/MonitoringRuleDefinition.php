<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use Icinga\Module\Vspheredb\Monitoring\SingleCheckResult;

use function in_array;

abstract class MonitoringRuleDefinition
{
    use TranslationHelper;

    public const SUPPORTED_OBJECT_TYPES = [];

    abstract public function getLabel(): string;
    abstract public static function getIdentifier(): string;
    abstract public function getParameters(): array;

    public static function isMultiInstanceRule(): bool
    {
        return false;
    }

    public static function supportsObjectType(string $objectType): bool
    {
        return in_array($objectType, static::SUPPORTED_OBJECT_TYPES, true);
    }

    /**
     * @param BaseDbObject $object
     * @param Settings $settings
     * @return SingleCheckResult[]
     */
    public function checkObject(BaseDbObject $object, Settings $settings): array
    {
        return [];
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function listParameters(): array
    {
        return array_keys($this->getParameters());
    }

    public function getInternalDefaults(): array
    {
        return [];
    }

    public function getSuggestedSettings(): array
    {
        return [];
    }

    protected function assertSupportedObject($object)
    {
        $type = ObjectType::getDbClassType(get_class($object));
        if (!static::supportsObjectType($type)) {
            throw new \RuntimeException(sprintf(
                "'%s' is not supported. Supported: %s",
                $type,
                implode(', ', static::SUPPORTED_OBJECT_TYPES)
            ));
        }
    }
}
