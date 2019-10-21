<?php

namespace Icinga\Module\Vspheredb\Web\Form\Element;

use ipl\Html\FormElement\BaseFormElement;

class Html extends BaseFormElement
{
    protected $tag = 'div';

    protected function registerCallbacks()
    {
        parent::registerCallbacks();
        $this->getAttributes()->registerAttributeCallback(
            'content',
            null,
            [$this, 'setContent']
        );
    }
}
