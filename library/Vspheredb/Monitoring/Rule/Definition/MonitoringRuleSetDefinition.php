<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

use gipfl\Translation\TranslationHelper;

abstract class MonitoringRuleSetDefinition
{
    use TranslationHelper;

    /** @var class-string<MonitoringRuleDefinition>[] */
    public const RULE_CLASSES = [];

    /** @var MonitoringRuleDefinition[]|null */
    protected ?array $rules = null;

    /**
     * @return MonitoringRuleDefinition[]
     */
    public function getRules(): array
    {
        if ($this->rules === null) {
            $this->rules = [];
            foreach (static::getRuleClasses() as $class) {
                $this->rules[] = new $class();
            }
        }

        return $this->rules;
    }

    abstract public function getLabel(): string;

    abstract public static function getIdentifier(): string;

    /**
     * @return MonitoringRuleDefinition[]|string[]
     */
    public static function getRuleClasses(): array
    {
        return static::RULE_CLASSES;
    }
}
