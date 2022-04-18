<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\RuleSetRegistry;

class InheritedSettings extends Settings
{
    /** @var MonitoringRulesTree */
    protected $tree;

    protected $inheritedFromUuids = [];
    protected $inheritedFromNames = [];

    public function __construct(MonitoringRulesTree $tree)
    {
        parent::__construct();
        $this->tree = $tree;
    }

    public static function loadFor($uuid, MonitoringRulesTree $tree, Db $db): InheritedSettings
    {
        return static::loadForUuids($tree->listParentUuidsFor($uuid), $tree, $db);
    }

    public static function loadForUuids($uuids, MonitoringRulesTree $tree, Db $db): InheritedSettings
    {
        $self = new static($tree);
        $self->tree = $tree;
        foreach ($uuids as $parentUuid) {
            $configured = MonitoringRuleSet::loadOptionalForUuid($parentUuid, $tree->getBaseObjectFolderName(), $db);
            if ($configured) {
                $self->applyInherited($configured->getSettings(), $parentUuid);
            }
        }

        return $self;
    }

    public function applyInherited(Settings $settings, $inheritedFrom = null)
    {
        foreach ($settings->settings as $key => $value) {
            $this->setInherited($key, $value, $inheritedFrom);
        }
    }

    public function getInheritedFromUuid($key): ?string
    {
        if ($key === null) {
            return null;
        }

        return $this->inheritedFromUuids[$key];
    }

    public function getInheritedFromName($key): ?string
    {
        if ($key === '') {
            return 'Fake root';
        }
        if (! isset($this->inheritedFromNames[$key])) {
            return null;
        }

        return $this->inheritedFromNames[$key];
    }

    public function dump(): array
    {
        $result = [];
        foreach ($this->inheritedFromNames as $key => $fromName) {
            $result[$key] = $this->get($key) . ' (' . $fromName . ')';
        }

        return $result;
    }

    /**
     * @param string $prefix
     * @return $this|InheritedSettings
     */
    public function withRemovedPrefix(string $prefix)
    {
        $length = strlen($prefix);
        $settings = new InheritedSettings($this->tree);
        foreach ($this->toArray() as $key => $value) {
            if (substr($key, 0, $length) === $prefix) {
                $settings->setInherited(substr($key, $length), $value, $this->getInheritedFromUuid($key));
            }
        }

        return $settings;
    }

    public function listMainInheritedKeys(): array
    {
        $keys = [];
        foreach ($this->inheritedFromUuids as $key => $uuid) {
            $pos = strpos($key, self::KEY_SEPARATOR);
            if ($pos !== false) {
                $keys[substr($key, 0, $pos)] = true;
            }
        }

        return array_keys($keys);
    }

    public function setInternalDefaults(RuleSetRegistry $registry)
    {
        foreach ($registry->getSets() as $set) {
            foreach ($set->getRules() as $rule) {
                $prefix = Settings::prefix($set, $rule);
                foreach ($rule->getInternalDefaults() as $key => $value) {
                    if (! $rule::isMultiInstanceRule()) {
                        $this->setInherited("$prefix$key", $value);
                    }
                    // TODO: Currently not applying multi-instance defaults. Affects one
                    // setting of one rule only, the rule handles this. To address this
                    // in a generic way, we would be required to set this for every local
                    // and inherited instance
                }
            }
        }
    }

    public function setInherited($name, $value, $inheritedFrom = null)
    {
        parent::set($name, $value);
        $this->inheritedFromUuids[$name] = $inheritedFrom;
        if ($inheritedFrom) {
            $this->inheritedFromNames[$name] = $this->tree->getNameForUuid($inheritedFrom);
        }
    }
}
