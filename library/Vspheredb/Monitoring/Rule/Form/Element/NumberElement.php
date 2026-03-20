<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Form\Element;

use ipl\Html\FormElement\NumberElement as Number;

class NumberElement extends Number
{
    public function getValue(): mixed
    {
        $value = parent::getValue();
        if (is_string($value)) {
            return ctype_digit($value) ? (int) $value : (float) $value;
        }

        return $value;
    }
}
