<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleDefinition as Rule;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleSetDefinition as RuleSet;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\RuleSetRegistry;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\MonitoringStateTrigger;
use InvalidArgumentException;
use ipl\Html\FormElement\NumberElement;
use ipl\Html\FormElement\SelectElement;
use ipl\Html\FormElement\TextElement;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RuleForm extends Form
{
    use TranslationHelper;

    public const NEXT_UUID = '00000000-0000-0000-0000-000000000000';
    public const RESULT_CREATED    = 'created';
    public const RESULT_MODIFIED   = 'modified';
    public const RESULT_UNMODIFIED = 'unmodified';
    public const RESULT_DELETED    = 'deleted';

    /** @var string */
    protected $objectType;

    /** @var string */
    protected $binaryUuid;

    /** @var Db */
    protected $db;

    /** @var InheritedSettings */
    protected $inherited;

    /** @var MonitoringRuleSet|null */
    protected $loadedSet;

    /** @var string Any of self::RESULT_* */
    protected $result;

    public function __construct(
        string $objectType,
        string $binaryUuid,
        Db $db,
        InheritedSettings $inherited,
        ?MonitoringRuleSet $loadedSet = null
    ) {
        $this->addPluginLoader('element', '\\Icinga\\Module\\Vspheredb\\Monitoring\\Rule\\Form\\Element', 'Element');
        $this->addAttributes(['class' => 'ruleform']);
        $this->objectType = $objectType;
        $this->db = $db;
        $this->binaryUuid = $binaryUuid;
        $this->inherited = $inherited;
        $this->loadedSet = $loadedSet;
        if ($loadedSet) {
            $this->populate((array) $loadedSet->getSettings()->jsonSerialize());
        }
    }

    protected function assemble()
    {
        $sets = RuleSetRegistry::default()->getSets();
        foreach ($sets as $set) {
            $firstOfSet = true;
            foreach ($set->getRules() as $rule) {
                if (! $rule::supportsObjectType($this->objectType)) {
                    continue;
                }
                $prefix = Settings::prefix($set);
                if ($firstOfSet) {
                    $this->add(Html::tag('h2', $set->getLabel()));
                    $this->addEnabledSetting($prefix);
                    $firstOfSet = null;
                }
                if ($rule::isMultiInstanceRule()) {
                    $inheritedInstances = $this->inherited->withRemovedPrefix(
                        Settings::prefix($set, $rule)
                    )->listMainInheritedKeys();
                    $inheritedInstances = array_combine($inheritedInstances, $inheritedInstances);
                    if ($this->loadedSet) {
                        $instances = InstanceKeys::getListFrom($this->loadedSet->getSettings()->toArray(), $set, $rule);
                        foreach ($instances as $instance) {
                            unset($inheritedInstances[$instance->toString()]);
                        }
                    } else {
                        $instances = [];
                    }
                    foreach ($inheritedInstances as $instance) {
                        $this->addRule($set, $rule, Uuid::fromString($instance));
                    }
                    foreach ($instances as $instance) {
                        $this->addRule($set, $rule, $instance);
                    }
                    $this->addRule($set, $rule, Uuid::fromString(self::NEXT_UUID));
                } else {
                    $this->addRule($set, $rule);
                }
            }
        }

        $this->applyInheritedInfo();
    }

    protected function addRule(RuleSet $set, Rule $rule, ?UuidInterface $instance = null)
    {
        if ($instance === null) {
            $this->add(Html::tag('h3', $rule->getLabel()));
        } else {
            if ($instance->toString() === self::NEXT_UUID) {
                $this->add(Html::tag('h3', $rule->getLabel() . sprintf(' (%s)', $this->translate('new instance'))));
            } else {
                $this->add(Html::tag('h3', $rule->getLabel() . sprintf(' (%s)', $instance->toString())));
            }
        }
        $prefix = Settings::prefix($set, $rule, $instance);
        $this->addEnabledSetting($prefix);
        foreach ($rule->getParameters() as $name => $definition) {
            $this->assertValidateParameterName($name);
            $elementName = $prefix . $name;
            $this->createRuleElement($elementName, $definition);
        }
    }

    protected function createRuleElement($elementName, $definition)
    {
        $elementType = array_shift($definition);
        $options = array_shift($definition) ?: [];
        // TODO: dedicated form element type
        if ($elementType === 'state_trigger') {
            $this->addStateTriggerElement($elementName, $options);
        } else {
            $this->addElement($elementType, $elementName, $options);
        }
    }

    protected function applyInheritedInfo()
    {
        foreach ((array) $this->inherited->jsonSerialize() as $key => $value) {
            $this->setInheritedValue($key, $value, $this->inherited->getInheritedFromName($key));
        }
    }

    protected function setInheritedValue($elementName, $value, $sourceName = null)
    {
        if ($this->getValue($elementName) !== null) {
            return;
        }
        if (! $this->hasElement($elementName)) { // happens when inheriting settings for another object type
            return;
        }
        $element = $this->getElement($elementName);
        $suffix = sprintf(
            ' (%s)',
            $sourceName
                ? sprintf($this->translate('inherited from %s'), $sourceName)
                : $this->translate('default')
        );

        if ($element instanceof SelectElement) {
            $optionValue = $value;
            if ($optionValue === true) {
                $optionValue = 'y';
            } elseif ($optionValue === false) {
                $optionValue = 'n';
            }
            $element->getOption(null)->setContent(
                implode(',', $element->getOption($optionValue)->getContent()) . $suffix
            );
        } elseif ($element instanceof TextElement || $element instanceof NumberElement) {
            $element->setAttribute('placeholder', $value . $suffix);
        }
    }

    protected function assertValidateParameterName($name)
    {
        if (! preg_match('/^[A-z]+[A-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("'$name' is not a valid parameter name");
        }
    }

    public function getNormalizedValues(): array
    {
        $result = [];

        $sets = RuleSetRegistry::default()->getSets();
        foreach ($sets as $set) {
            $firstOfSet = true;
            foreach ($set->getRules() as $rule) {
                if (! $rule::supportsObjectType($this->objectType)) {
                    continue;
                }
                $prefix = Settings::prefix($set);
                if ($firstOfSet) {
                    $this->applyResultValue($result, $prefix, Settings::KEY_ENABLED, 'boolean');
                    $firstOfSet = null;
                }
                if ($rule::isMultiInstanceRule()) {
                    $instances = InstanceKeys::getListFrom($this->getValues(), $set, $rule);
                    foreach ($instances as $instance) {
                        $prefix = Settings::prefix($set, $rule, $instance);
                        $storingPrefix = Settings::prefix(
                            $set,
                            $rule,
                            $instance->toString() === self::NEXT_UUID ? Uuid::uuid4() : $instance
                        );
                        $this->applyResultValue($result, $storingPrefix, Settings::KEY_ENABLED, 'boolean');
                        foreach ($rule->getParameters() as $name => $definition) {
                            $this->assertValidateParameterName($name);
                            $this->applyResultValue($result, $prefix, $name, $definition[0], $storingPrefix);
                        }
                    }
                } else {
                    $prefix = Settings::prefix($set, $rule);
                    $this->applyResultValue($result, $prefix, Settings::KEY_ENABLED, 'boolean');
                    foreach ($rule->getParameters() as $name => $definition) {
                        $this->assertValidateParameterName($name);
                        $this->applyResultValue($result, $prefix, $name, $definition[0]);
                    }
                }
            }
        }

        return $result;
    }

    protected function applyResultValue(&$values, $prefix, $key, $elementType, $storingPrefix = null)
    {
        $storingKey = ($storingPrefix ?? $prefix) . $key;
        $key = $prefix . $key;
        $value = $this->getValue($key);
        if ($elementType === 'boolean') {
            $value = $this->normalizeBoolean($value);
        }
        if ($value !== null) {
            $values[$storingKey] = $value;
        }
    }

    protected function normalizeBoolean($value): ?bool
    {
        switch ($value) {
            case null:
                return null;
            case 'y':
                return true;
            case 'n':
                return false;
        }

        throw new \RuntimeException("'$value' is not a valid boolean value");
    }

    protected function addEnabledSetting($prefix)
    {
        $elementName = $prefix . Settings::KEY_ENABLED;
        $this->addElement('boolean', $elementName, [
            'label'   => $this->translate('Enabled'),
            // 'class' => 'autosubmit',
        ]);
        $this->setInheritedValue($elementName, true);
    }

    protected function addStateTriggerElement(string $name, $options = [])
    {
        $selectOptions = [
            null => $this->translate('Not configured / Inherited'),
            MonitoringStateTrigger::IGNORE => $this->translate('Do nothing'),
            MonitoringStateTrigger::RAISE_WARNING => $this->translate('Trigger a Warning state'),
            MonitoringStateTrigger::RAISE_CRITICAL => $this->translate('Trigger a Critical state'),
            MonitoringStateTrigger::RAISE_UNKNOWN => $this->translate('Trigger an Unknown state'),
        ];

        $this->addElement('select', $name, [
            'options' => $selectOptions,
        ] + $options);
    }

    public function hasBeenCreated(): bool
    {
        return $this->result === self::RESULT_CREATED;
    }

    public function hasBeenModified(): bool
    {
        return $this->result === self::RESULT_MODIFIED;
    }

    public function hasNotBeenModified(): bool
    {
        return $this->result === self::RESULT_UNMODIFIED;
    }

    public function hasBeenDeleted(): bool
    {
        return $this->result === self::RESULT_DELETED;
    }


    protected function onSuccess()
    {
        $values = $this->getNormalizedValues();
        $settings = new Settings($values);
        if ($this->loadedSet) {
            $set = $this->loadedSet;
            $set->setSettings($settings);
        } else {
            $set = new MonitoringRuleSet($this->binaryUuid, $this->objectType, $settings);
        }
        if (empty($values)) {
            if ($set->delete($this->db)) {
                $result = self::RESULT_DELETED;
            } else {
                $result = self::RESULT_UNMODIFIED; // No different message for now
            }
        } else {
            if ($set->hasBeenLoadedFromDb()) {
                $result = $set->store($this->db) ? self::RESULT_MODIFIED : self::RESULT_UNMODIFIED;
            } else {
                $set->store($this->db);
                $result = self::RESULT_CREATED;
            }
        }

        $this->result = $result;
    }
}
