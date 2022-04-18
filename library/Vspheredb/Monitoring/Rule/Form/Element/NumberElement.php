<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Form\Element;

use ipl\Html\FormElement\NumberElement as Number;

class NumberElement extends Number
{
    public function getValue()
    {
        $value = parent::getValue();
        if (is_string($value)) {
            if (ctype_digit($value)) {
                return (int) $value;
            } else {
                return (float) $value;
            }
        }

        return $value;
    }
}
