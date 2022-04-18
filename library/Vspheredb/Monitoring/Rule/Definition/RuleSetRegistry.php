<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use gipfl\Json\JsonSerialization;
use RuntimeException;

class RuleSetRegistry implements JsonSerialization
{
    /** @var MonitoringRuleSetDefinition[] */
    protected $sets = [];

    /**
     * @param string[]|MonitoringRuleSetDefinition[] $sets
     */
    public function __construct(array $sets = [])
    {
        foreach ($sets as $set) {
            $this->loadSet($set);
        }
    }

    /**
     * @return MonitoringRuleSetDefinition[]|string[]
     */
    public function getSets(): array
    {
        return $this->sets;
    }

    public static function default(): RuleSetRegistry
    {
        return new static([
            DefaultRuleSet::class,
            DiskHealthRuleSet::class,
            ConfigurationPolicyRuleSet::class,
        ]);
    }

    public function loadSet(string $class)
    {
        $set = new $class();
        $name = $set::getIdentifier();
        if (isset($this->sets[$name])) {
            throw new RuntimeException("Cannot add set '$name' twice");
        }

        $this->sets[$name] = $set;
    }

    public static function fromSerialization($any)
    {
        return new static((array) $any);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $result = [];
        foreach ($this->sets as $set) {
            $result[$set::getIdentifier()] = $set;
        }

        return (object) $result;
    }
}
