<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Director;

use Icinga\Module\Director\Hook\DataTypeHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\RuleSetRegistry;

class DataTypeMonitoringRule extends DataTypeHook
{
    protected $db;

    public function getFormElement($name, QuickForm $form)
    {
        $registry = RuleSetRegistry::default();
        $options = [];
        foreach ($registry->getSets() as $set) {
            $current = [];
            $current[$set::getIdentifier() . '/*'] = sprintf(
                '* ' . $form->translate('(full rule set: %s)'),
                $set->getLabel()
            );
            foreach ($set->getRules() as $rule) {
                $current[$set::getIdentifier() . '/' . $rule::getIdentifier()] = $rule->getLabel();
            }
            $options[$set->getLabel()] = $current;
        }
        return $form->createElement('select', $name, [
            'multiOptions' => [null => $form->translate('- please choose -')] +
                $options,
        ]);
    }
}
