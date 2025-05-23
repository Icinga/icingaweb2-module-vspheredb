<?php

namespace Icinga\Module\Vspheredb\Monitoring;

use Exception;
use gipfl\Cli\Screen;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\RuleSetRegistry;
use Icinga\Module\Vspheredb\Monitoring\Rule\InheritedSettings;
use Icinga\Module\Vspheredb\Monitoring\Rule\MonitoringRulesTree;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use InvalidArgumentException;
use RuntimeException;

class CheckRunner
{
    public const RULESET_NAME_PARAMETER = 'ruleset';
    public const RULE_NAME_PARAMETER = 'rule';

    /** @var Db */
    protected $db;

    /** @var Screen */
    protected $screen;

    /** @var string */
    protected $ruleSetName;

    /** @var string */
    protected $ruleName;

    /** @var bool */
    protected $inspect = false;

    protected $preloadedTrees = [];

    public function __construct(Db $db)
    {
        $this->db = $db;
        $this->screen = Screen::factory();
    }

    public function setRuleSetName(string $section): void
    {
        $this->ruleSetName = $section;
    }

    public function setRuleName(string $ruleName): void
    {
        if ($this->ruleSetName === null) {
            throw new InvalidArgumentException('Cannot specify a Rule name without choosing a Rule Set first');
        }
        $this->ruleName = $ruleName;
    }

    public function enableInspection(bool $inspect = true): void
    {
        $this->inspect = $inspect;
    }

    public function preloadTreeFor($type)
    {
        $this->preloadedTrees[$type] = new MonitoringRulesTree($this->db, $type);
    }

    public function check(BaseDbObject $object): CheckResultSet
    {
        $type = self::getCheckTypeForObject($object);
        $registry = $this->getRegistry();
        $settings = $this->getSettingsForObject($object, $registry, $type);

        $all = new CheckResultSet(sprintf('%s, according configured rules', $this->getTypeLabelForObject($object)));
        $final = $this->ruleSetName === null ? $all : null;
        foreach ($registry->getSets() as $set) {
            if ($settings->isDisabled($set)) {
                if ($this->ruleSetName === $set::getIdentifier()) {
                    throw new RuntimeException(sprintf(
                        'Cannot run checks for Rule Set "%s", it has been disabled',
                        $this->ruleSetName
                    ));
                }
                continue;
            }
            if ($this->inspect) {
                $labelPostfix = $this->light(sprintf(
                    ' (--%s %s/*)',
                    self::RULE_NAME_PARAMETER,
                    $set::getIdentifier()
                ));
            } else {
                $labelPostfix = '';
            }

            $ruleSetResult = new CheckResultSet($set->getLabel() . $labelPostfix);
            if ($final === null && $this->ruleName === null && $this->ruleSetName === $set::getIdentifier()) {
                $final = $ruleSetResult;
            }
            $all->addResult($ruleSetResult);
            foreach ($set->getRules() as $rule) {
                if ($settings->isDisabled($set, $rule)) {
                    if ($this->ruleName === $rule::getIdentifier()) {
                        throw new RuntimeException(sprintf(
                            'Cannot run checks for Rule "%s", it has been disabled',
                            $this->ruleName
                        ));
                    }
                    continue;
                }
                if (!$rule::supportsObjectType($type)) {
                    if ($this->ruleName === $rule::getIdentifier()) {
                        throw new RuntimeException(sprintf(
                            'Cannot run checks for Rule "%s", it does not support "%s" objects',
                            $this->ruleName,
                            $type
                        ));
                    }
                    continue;
                }
                $ruleSettings = $settings->withRemovedPrefix(Settings::prefix($set, $rule));
                if ($this->inspect) {
                    $labelPostfix = $this->light(sprintf(
                        ' (--%s %s/%s)',
                        self::RULE_NAME_PARAMETER,
                        $set::getIdentifier(),
                        $rule::getIdentifier()
                    ));
                } else {
                    $labelPostfix = '';
                }
                $ruleResult = new CheckResultSet($rule->getLabel() . $labelPostfix);
                if ($final === null && $this->ruleName === $rule::getIdentifier()) {
                    $final = $ruleResult;
                }
                $ruleSetResult->addResult($ruleResult);
                if ($this->inspect) {
                    $ruleResult->prependOutput($this->light(rtrim(PlainSettingsRenderer::render($ruleSettings))));
                }
                try {
                    $results = $rule->checkObject($object, $ruleSettings);
                } catch (Exception $e) {
                    $results = [
                        new SingleCheckResult(new CheckPluginState(CheckPluginState::UNKNOWN), $e->getMessage())
                    ];
                }
                foreach ($results as $result) {
                    $ruleResult->addResult($result);
                }
            }
        }
        if ($final === null) {
            throw new RuntimeException('No check has been executed');
        }

        return $final;
    }

    protected function getRegistry(): RuleSetRegistry
    {
        if ($this->ruleSetName) {
            return RuleSetRegistry::byName($this->ruleSetName);
        } else {
            return RuleSetRegistry::default();
        }
    }

    protected function getSettingsForObject(
        BaseDbObject $object,
        RuleSetRegistry $registry,
        string $type
    ): InheritedSettings {
        $tree = $this->preloadedTrees[$type] ?? new MonitoringRulesTree($this->db, $type);
        $settings = $tree->getInheritedSettingsFor($object);
        $settings->setInternalDefaults($registry);

        return $settings;
    }

    /**
     * @param BaseDbObject $object
     * @return array<string, int>
     */
    public function checkForDb(BaseDbObject $object): array
    {
        $type = self::getCheckTypeForObject($object);
        $registry = $this->getRegistry();
        $settings = $this->getSettingsForObject($object, $registry, $type);
        $results = [];
        foreach ($registry->getSets() as $set) {
            if ($settings->isDisabled($set)) {
                continue;
            }
            $ruleSetResult = new CheckResultSet($set->getLabel());
            foreach ($set->getRules() as $rule) {
                if ($settings->isDisabled($set, $rule)) {
                    continue;
                }
                if (!$rule::supportsObjectType($type)) {
                    continue;
                }
                $ruleSettings = $settings->withRemovedPrefix(Settings::prefix($set, $rule));
                $ruleResult = new CheckResultSet($rule->getLabel());
                $ruleSetResult->addResult($ruleResult);
                foreach ($rule->checkObject($object, $ruleSettings) as $result) {
                    $ruleResult->addResult($result);
                }
                $results[$set::getIdentifier() . '/' . $rule::getIdentifier()] = $ruleResult;
            }
        }

        return $results;
    }

    protected function getTypeLabelForObject(BaseDbObject $object): string
    {
        if ($object instanceof HostSystem) {
            return 'Host System';
        } elseif ($object instanceof VirtualMachine) {
            return 'Virtual Machine';
        } elseif ($object instanceof Datastore) {
            return 'Datastore';
        }

        return 'Object';
    }

    public static function getCheckTypeForObject(BaseDbObject $object): string
    {
        if ($object instanceof HostSystem) {
            return 'host';
        } elseif ($object instanceof VirtualMachine) {
            return 'vm';
        } elseif ($object instanceof Datastore) {
            return 'datastore';
        }

        throw new InvalidArgumentException('Check commands are not supported for ' . get_class($object));
    }

    protected function light(string $string): string
    {
        return $this->screen->colorize($string, 'lightgray');
    }
}
