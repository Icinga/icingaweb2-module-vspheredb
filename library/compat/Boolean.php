<?php

namespace gipfl\Web\Form\Element;

use ipl\Html\FormElement\SelectElement;

// This is an exact copy of gipfl/web src/Form/Element/Boolean.php, intentionally
// loaded without the TranslationHelper trait from gipfl/translation. That trait
// declares translate(string $string, string $context = null) without the return
// type hint, which is incompatible with ipl\Html\FormElement\BaseFormElement::translate()
// after ipl/i18n introduced strict typing. Loading the original class via its
// autoloader would pull in TranslationHelper and produce a fatal:
//   Declaration of gipfl\Web\Form\Element\Boolean::translate() must be compatible
//   with ipl\Html\FormElement\BaseFormElement::translate(string, ?string): string
// By preloading this copy here we let BaseFormElement's own translate() handle
// translations and avoid the inheritance conflict entirely.

if (! class_exists(__NAMESPACE__ . '\Boolean', false)) {
    class Boolean extends SelectElement
    {
        public function __construct($name, $attributes = null)
        {
            parent::__construct($name, $attributes);
            $options = [
                'y'  => $this->translate('Yes'),
                'n'  => $this->translate('No'),
            ];
            if (! $this->isRequired()) {
                $options = ['' => $this->translate('- please choose -')] + $options;
            }

            $this->setOptions($options);
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
}
