<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleDefinition as Rule;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleSetDefinition as RuleSet;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class InstanceKeys
{
    /**
     * @param array $values
     * @param RuleSet $set
     * @param Rule|null $rule
     * @return UuidInterface[] List of UUIDs
     */
    public static function getListFrom(array $values, RuleSet $set, Rule $rule): array
    {
        $prefix = Settings::prefix($set, $rule);
        $keys = [];
        $pattern = '/' . preg_quote(Settings::KEY_SEPARATOR, '/') . '.+$/';
        $length = strlen($prefix);
        foreach ($values as $key => $value) {
            if (substr($key, 0, $length) === $prefix) {
                $foundKey = preg_replace($pattern, '', substr($key, $length));
                $uuid = Uuid::fromString($foundKey);
                $keys[$uuid->toString()] = $uuid;
            }
        }

        return array_values($keys);
    }
}
