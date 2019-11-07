<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormDecorator\DecoratorInterface;
use ipl\Html\FormElement\BaseFormElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\Html;

class LegacyDecorator extends BaseHtmlElement implements DecoratorInterface
{
    /** @var BaseFormElement The decorated form element */
    protected $formElement;

    /** @var bool Whether the form element has been added already */
    protected $formElementAdded = false;

    protected $tag = 'div';

    public function decorate(BaseFormElement $formElement)
    {
        $decorator = clone $this;

        $decorator->formElement = $formElement;

        // TODO(el): Replace with SubmitElementInterface once introduced
        if ($formElement instanceof SubmitElement) {
            $class = 'control-group form-controls';

            $formElement->getAttributes()->add(['class' => 'btn-primary']);
        } else {
            $class = 'control-group';
        }

        $decorator->getAttributes()->add('class', $class);

        $formElement->prependWrapper($decorator);

        return $decorator;
    }

    protected function assembleDescription()
    {
        $description = $this->formElement->getDescription();

        if ($description !== null) {
            return Html::tag('p', ['class' => 'form-element-description'], $description);
        }

        return null;
    }

    protected function assembleErrors()
    {
        $errors = [];

        foreach ($this->formElement->getMessages() as $message) {
            $errors[] = Html::tag('li', $message);
        }

        if (! empty($errors)) {
            return Html::tag('ul', ['class' => 'errors'], $errors);
        }

        return null;
    }

    protected function assembleLabel()
    {
        $label = $this->formElement->getLabel();

        if ($label !== null) {
            $attributes = null;

            if ($this->formElement->getAttributes()->has('id')) {
                $attributes = new Attributes(['for' => $this->formElement->getAttributes()->get('id')]);
            }

            return Html::tag('div', ['class' => 'control-label-group'], Html::tag('label', $attributes, $label));
        }

        return null;
    }

    public function add($content)
    {
        if ($content === $this->formElement) {
            // Our wrapper implementation automatically adds the wrapped element but we already did this in assemble
            if ($this->formElementAdded) {
                return $this;
            }

            $this->formElementAdded = true;
        }

        parent::add($content);

        return $this;
    }

    protected function assemble()
    {
        if ($this->formElement->hasBeenValidatedAndIsNotValid()) {
            $this->getAttributes()->add('class', 'has-error');
        }

        $this->add(array_filter([
            $this->assembleLabel(),
            $this->formElement,
            $this->assembleDescription(),
            $this->assembleErrors()
        ]));
    }
}
