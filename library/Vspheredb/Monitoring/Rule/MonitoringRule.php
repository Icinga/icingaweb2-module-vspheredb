<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use gipfl\Json\JsonSerialization;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleSetDefinition;

class MonitoringRule implements JsonSerialization
{
    protected $enabled = true;

    /** @var MonitoringRuleSetDefinition */
    protected $definition;

    public function __construct(MonitoringRuleSetDefinition $definition)
    {
        $this->definition = $definition;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return MonitoringRuleSetDefinition
     */
    public function getDefinition(): MonitoringRuleSetDefinition
    {
        return $this->definition;
    }

    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }

    public static function fromSerialization($any)
    {
        // TODO: Implement fromSerialization() method.
    }
}
