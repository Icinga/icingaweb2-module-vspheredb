<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Json\JsonString;
use gipfl\Web\Form;
use gipfl\Web\Form\Decorator\DdDtDecorator;
use ipl\Html\FormElement\SubmitElement;

trait FormElementStealer
{
    protected $mainProperties = [];

    public function getValues()
    {
        $values = parent::getValues();
        $mainProperties = array_merge($this->mainProperties, [
            'settings',
        ]);
        $finalValues = [];
        $settings = [];
        foreach ($values as $key => $value) {
            if (in_array($key, $mainProperties)) {
                $finalValues[$key] = $value;
            } else {
                $settings[$key] = $value;
            }
        }
        $finalValues['settings'] = JsonString::encode($settings);

        return $finalValues;
    }

    protected function addButtons($final, $selectProperty)
    {
        if ($final) {
            $submit = new SubmitElement('submit', [
                'label' => $this->isNew() ? $this->translate('Create') : $this->translate('Store')
            ]);
            $this->addElement($submit);
            $this->setSubmitButton($submit);
            $deco = $submit->getWrapper();
            assert($deco instanceof DdDtDecorator);

            if ($this->isNew()) {
                $back = new SubmitElement('btn_back', [
                    'label' => $this->translate('Back'),
                    'formnovalidate' => true,
                ]);
                $deco->dd()->add($back);
                $this->registerElement($back);
                if ($back->hasBeenPressed()) {
                    $this->setElementValue($selectProperty, null);
                }
            } else {
                $delete = new SubmitElement('btn_delete', [
                    'label' => $this->translate('Delete')
                ]);
                $deco->dd()->add($delete);
                $this->registerElement($delete);
                if ($delete->hasBeenPressed()) {
                    $this->store->delete($this->object);
                    $this->emit(self::ON_DELETE, [$this]);
                }
            }
        } else {
            $this->addElement('submit', 'next', [
                'label' => $this->translate('Next')
            ]);
        }
    }

    protected function addFormElementsFrom(Form $form)
    {
        foreach ($this->getElements() as $mainElement) {
            if (! $mainElement->isIgnored()) {
                $this->mainProperties[] = $mainElement->getName();
            }
        }

        /** @var ObjectForm $this */
        $form->disableCsrf();
        $form->doNotCheckFormName();
        if ($object = $this->getObject()) {
            if (method_exists($object, 'settings')) {
                $populate = [];
                foreach ($object->settings() as $key => $value) {
                    $populate[] = $key;
                }
                foreach ($populate as $key) {
                    $form->populate($populate);
                }
            }
        }

        // TODO: Clone Request, strip our values from request?
        $form->handleRequest($this->getRequest());
        $form->ensureAssembled();
        $this->add($form->getContent());
        foreach ($form->getElements() as $element) {
            $this->registerElement($element);
        }
        // $form->populate($this->getSentValues());
    }
}
