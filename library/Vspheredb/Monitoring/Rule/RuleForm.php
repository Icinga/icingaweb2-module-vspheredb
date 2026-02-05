<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleDefinition as Rule;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\MonitoringRuleSetDefinition as RuleSet;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\RuleSetRegistry;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\MonitoringStateTrigger;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ResultStatus;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\FormElement\NumberElement;
use ipl\Html\FormElement\SelectElement;
use ipl\Html\FormElement\TextElement;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

class RuleForm extends Form
{
    use TranslationHelper;

    public const NEXT_UUID = '00000000-0000-0000-0000-000000000000';

    /** @var ObjectType */
    protected ObjectType $objectType;

    /** @var string */
    protected string $binaryUuid;

    /** @var Db */
    protected Db $db;

    /** @var InheritedSettings */
    protected InheritedSettings $inherited;

    /** @var ?MonitoringRuleSet */
    protected ?MonitoringRuleSet $loadedSet;

    /** @var ?ResultStatus Any of self::RESULT_* */
    protected ?ResultStatus $result = null;

    public function __construct(
        ObjectType $objectType,
        string $binaryUuid,
        Db $db,
        InheritedSettings $inherited,
        ?MonitoringRuleSet $loadedSet = null
    ) {
        $this->addPluginLoader('element', '\\Icinga\\Module\\Vspheredb\\Monitoring\\Rule\\Form\\Element', 'Element');
        $this->addAttributes(Attributes::create(['class' => 'ruleform']));
        $this->objectType = $objectType;
        $this->db = $db;
        $this->binaryUuid = $binaryUuid;
        $this->inherited = $inherited;
        $this->loadedSet = $loadedSet;
        if ($loadedSet) {
            $this->populate((array) $loadedSet->getSettings()->jsonSerialize());
        }
    }

    protected function assemble(): void
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

    protected function addRule(RuleSet $set, Rule $rule, ?UuidInterface $instance = null): void
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

    protected function createRuleElement($elementName, $definition): void
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

    protected function applyInheritedInfo(): void
    {
        foreach ((array) $this->inherited->jsonSerialize() as $key => $value) {
            $this->setInheritedValue($key, $value, $this->inherited->getInheritedFromName($key));
        }
    }

    protected function setInheritedValue(string $elementName, mixed $value, ?string $sourceName = null): void
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

    protected function assertValidateParameterName($name): void
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

    protected function applyResultValue(
        array &$values,
        string $prefix,
        string $key,
        string $elementType,
        ?string $storingPrefix = null
    ): void {
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

    protected function normalizeBoolean(?string $value): ?bool
    {
        return match ($value) {
            null    => null,
            'y'     => true,
            'n'     => false,
            default => throw new RuntimeException("'$value' is not a valid boolean value")
        };
    }

    protected function addEnabledSetting(string $prefix): void
    {
        $elementName = $prefix . Settings::KEY_ENABLED;
        $this->addElement('boolean', $elementName, [
            'label'   => $this->translate('Enabled'),
            // 'class' => 'autosubmit'
        ]);
        $this->setInheritedValue($elementName, true);
    }

    protected function addStateTriggerElement(string $name, array $options = []): void
    {
        $selectOptions = [
            '' => $this->translate('Not configured / Inherited'),
            MonitoringStateTrigger::IGNORE->value => $this->translate('Do nothing'),
            MonitoringStateTrigger::RAISE_WARNING->value => $this->translate('Trigger a Warning state'),
            MonitoringStateTrigger::RAISE_CRITICAL->value => $this->translate('Trigger a Critical state'),
            MonitoringStateTrigger::RAISE_UNKNOWN->value => $this->translate('Trigger an Unknown state')
        ];

        $this->addElement('select', $name, ['options' => $selectOptions] + $options);
    }

    public function hasBeenCreated(): bool
    {
        return $this->result === ResultStatus::CREATED;
    }

    public function hasBeenModified(): bool
    {
        return $this->result === ResultStatus::MODIFIED;
    }

    public function hasNotBeenModified(): bool
    {
        return $this->result === ResultStatus::UNMODIFIED;
    }

    public function hasBeenDeleted(): bool
    {
        return $this->result === ResultStatus::DELETED;
    }


    protected function onSuccess(): void
    {
        $values = $this->getNormalizedValues();
        $settings = new Settings($values);
        if ($this->loadedSet) {
            $set = $this->loadedSet;
            $set->setSettings($settings);
        } else {
            $set = new MonitoringRuleSet($this->binaryUuid, $this->objectType->value, $settings);
        }
        if (empty($values)) {
            if ($set->delete($this->db)) {
                $this->result = ResultStatus::DELETED;
            } else {
                $this->result = ResultStatus::UNMODIFIED; // No different message for now
            }
        } else {
            if ($set->hasBeenLoadedFromDb()) {
                $this->result = $set->store($this->db) ? ResultStatus::MODIFIED : ResultStatus::UNMODIFIED;
            } else {
                $set->store($this->db);
                $this->result = ResultStatus::CREATED;
            }
        }
    }
}
