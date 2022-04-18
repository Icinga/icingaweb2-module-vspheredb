<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use gipfl\DataType\Settings as SettingsDataType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleDefinition as Rule;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleSetDefinition as RuleSet;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;

class Settings extends SettingsDataType
{
    const KEY_SEPARATOR = '/';
    public const KEY_ENABLED = '_enabled';

    public function isDisabled(?RuleSet $set = null, ?Rule $rule = null): bool
    {
        return $this->get(static::prefix($set, $rule) . self::KEY_ENABLED) === false;
    }

    public function toArray(): array
    {
        return (array) $this->jsonSerialize();
    }

    public function listMainKeys(): array
    {
        $keys = [];
        foreach ($this->toArray() as $key => $value) {
            $pos = strpos($key, self::KEY_SEPARATOR);
            if ($pos !== false) {
                $keys[substr($key, 0, $pos)] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * @param string $key
     * @return $this|Settings
     */
    public function withRemovedKey(string $key)
    {
        return $this->withRemovedPrefix($key . Settings::KEY_SEPARATOR);
    }

    /**
     * @param string $prefix
     * @return $this|Settings
     */
    public function withRemovedPrefix(string $prefix)
    {
        $length = strlen($prefix);
        $settings = new Settings();
        foreach ($this->toArray() as $key => $value) {
            if (substr($key, 0, $length) === $prefix) {
                $settings->set(substr($key, $length), $value);
            }
        }

        return $settings;
    }

    public static function prefix(?RuleSet $set = null, ?Rule $rule = null, ?UuidInterface $instance = null): string
    {
        if ($set === null) {
            if ($rule !== null) {
                throw new InvalidArgumentException('Rule requires a Set');
            } elseif ($instance !== null) {
                throw new InvalidArgumentException('Rule instance requires a rule');
            }
            return '';
        }
        $prefix = $set::getIdentifier() . self::KEY_SEPARATOR;
        if ($rule) {
            if ($instance === null) {
                $prefix .= $rule::getIdentifier() . self::KEY_SEPARATOR;
            } else {
                $prefix .= $rule::getIdentifier() . self::KEY_SEPARATOR . $instance->toString() . self::KEY_SEPARATOR;
            }
        } elseif ($instance) {
            throw new InvalidArgumentException('Rule instance requires a rule');
        }

        return $prefix;
    }
}
