<?php

namespace Icinga\Module\Vspheredb\Monitoring\Table;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\CheckResultSet;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\RuleSetRegistry;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\InheritedSettings;
use Icinga\Module\Vspheredb\Monitoring\Rule\MonitoringRulesTree;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Table;

class ObjectRulesCheckTable extends Table
{
    use TranslationHelper;

    protected $defaultAttributes = [
        'class' => [
            'common-table',
            'object-rules-check-table',
        ]
    ];

    /** @var BaseDbObject */
    protected $object;

    /** @var Db */
    protected $db;

    /** @var string */
    protected $type;

    /** @var bool */
    protected $showSettings = false;

    public function __construct(BaseDbObject $object, Db $db)
    {
        $this->object = $object;
        $this->type = ObjectType::getDbObjectType($object);
        $this->db = $db;
    }

    public function showSettings(bool $show = true)
    {
        $this->showSettings = $show;
    }

    protected function assemble()
    {
        $tree = new MonitoringRulesTree($this->db, $this->type);
        $settings = $tree->getInheritedSettingsFor($this->object);
        $settings->setInternalDefaults(RuleSetRegistry::default());
        $all = new CheckResultSet('Monitoring Rules');

        foreach (RuleSetRegistry::default()->getSets() as $set) {
            $this->add($this::row([$set->getLabel()], null, 'th'));
            if ($settings->isDisabled($set)) {
                $this->add($this::row([$this->translate('Check set has been disabled')]));
                continue;
            }

            $checkSet = new CheckResultSet($set->getLabel());
            $all->addResult($checkSet);
            foreach ($set->getRules() as $rule) {
                if (!$rule::supportsObjectType($this->type)) {
                    continue;
                }
                if ($settings->isDisabled($set, $rule)) {
                    $this->add($this::row([$this->translate('Check rule has been disabled')]));
                    continue;
                }
                $ruleSettings = $settings->withRemovedPrefix(Settings::prefix($set, $rule));
                $content = [];
                foreach ($rule->checkObject($this->object, $ruleSettings) as $result) {
                    if (count($content) > 0) {
                        $content[] = Html::tag('br');
                    }
                    $content[] = Html::tag('span', [
                       'class' => ['badge', 'state-' . strtolower($result->getState()->getName())]
                    ], $result->getState()->getName());
                    $content[] =  $result->getOutput();
                    $checkSet->addResult($result);
                    if ($this->showSettings) {
                        $content[] = $this->renderSettings($ruleSettings);
                    }
                }
                if (empty($content)) {
                    if ($this->showSettings) {
                        $this->add($this::row([$rule->getLabel(), $this->renderSettings($ruleSettings)]));
                    }
                } else {
                    $this->add($this::row([$rule->getLabel(), $content]));
                }
            }
        }
    }

    protected function renderSettings(InheritedSettings $settings): HtmlElement
    {
        $output = Html::tag('pre');
        foreach ($settings->toArray() as $name => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) { // Impossible
                $value = 'null';
            } elseif (is_string($value)) {
                $value = '"' . addcslashes($value, '"') . '"';
            }
            if ($from = $settings->getInheritedFromName($name)) {
                $value .= sprintf(' (%s)', sprintf($this->translate('inherited from %s'), $from));
            }

            $output->add("$name = $value\n");
        }

        return $output;
    }
}
