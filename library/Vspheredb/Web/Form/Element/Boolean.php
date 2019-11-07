<?php

namespace Icinga\Module\Vspheredb\Web\Form\Element;

use gipfl\Translation\TranslationHelper;
use ipl\Html\FormElement\SelectElement;

class Boolean extends SelectElement
{
    use TranslationHelper;

    public function __construct($name, $attributes = null)
    {
        parent::__construct($name, $attributes);

        $this->setOptions([
            null => $this->translate('- please choose -'),
            'y'  => 'Yes',
            'n'  => 'No',
        ]);
    }

    public function setValue($value)
    {
        if ($value === 'y' || $value === true) {
            return parent::setValue('y');
        } elseif ($value === 'n' || $value === false) {
            return parent::setValue('n');
        }

        // Hint: this will fail
        return parent::setValue($value);
    }
}
