<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use Icinga\Application\Version;
use Icinga\Module\Vspheredb\Validator\PhpSessionBasedCsrfTokenValidator;
use ipl\Html\Form as iplForm;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\Html;
use RuntimeException;

class Form extends iplForm
{
    use TranslationHelper;

    protected $formNameElementName = '__FORM_NAME';

    public function ensureAssembled()
    {
        if ($this->hasBeenAssembled === false) {
            if ($this->getRequest() === null) {
                throw new RuntimeException('Cannot assemble a WebForm without a Request');
            }
            $this->addElementLoader(__NAMESPACE__ . '\\Element');
            parent::ensureAssembled();
            $this->prepareWebForm();
        }

        return $this;
    }

    protected function prepareWebForm()
    {
        $this->addFormNameElement();
        if ($this->getMethod() === 'POST') {
            $this->addCsrfElement();
        }
        $this->setupStyling();
    }

    protected function getUniqueFormName()
    {
        return \get_class($this);
    }

    protected function addFormNameElement()
    {
        $this->addElement('hidden', $this->formNameElementName, [
            'value'  => $this->getUniqueFormName(),
            'ignore' => true,
        ]);
    }

    protected function setupStyling()
    {
        if (\version_compare(Version::VERSION, '2.7.0', '<')) {
            $this->getAttributes()->add('class', 'form-compat26');
        }
        $this->addAttributes([
            'class' => 'icinga-form icinga-controls'
        ]);
        $this->setDefaultElementDecorator(new LegacyDecorator());
    }

    protected function addCsrfElement()
    {
        $element = new HiddenElement('__CSRF__', [
            'ignore' => true,
        ]);
        $element->setValidators([
            new PhpSessionBasedCsrfTokenValidator()
        ]);
        $this->addElement($element);
        if ($this->hasBeenSent()) {
            if (! $element->isValid()) {
                $element->setValue($this->generateCsrfValue());
            }
        } else {
            $element->setValue($this->generateCsrfValue());
        }
    }

    public function getSentValue($name, $default = null)
    {
        $request = $this->getRequest();
        if ($request === null) {
            throw new RuntimeException(
                "It's impossible to access SENT values with no request"
            );
        }
        if ($request->getMethod() === 'POST') {
            $params = $request->getParsedBody();
        } elseif ($this->getMethod() === 'GET') {
            \parse_str($request->getUri()->getQuery(), $params);
        } else {
            $params = [];
        }

        if (\array_key_exists($name, $params)) {
            return $params[$name];
        } else {
            return $default;
        }
    }

    public function onSuccess()
    {
        // Do not show default success message
    }

    public function hasBeenSent()
    {
        if (parent::hasBeenSent()) {
            return $this->getSentValue($this->formNameElementName)
                === $this->getUniqueFormName();
        } else {
            return false;
        }
    }

    protected function generateCsrfValue()
    {
        $seed = \mt_rand();
        $token = hash('sha256', \session_id() . $seed);

        return sprintf('%s|%s', $seed, $token);
    }

    // TODO: The decorator should take care, shouldn't it?
    public function onError()
    {
        foreach ($this->getMessages() as $message) {
            if ($message instanceof \Exception) {
                $message = $message->getMessage();
            }
            $this->prepend(Html::tag('p', ['class' => 'state-hint error'], $message));
        }
        foreach ($this->getElements() as $element) {
            foreach ($element->getMessages() as $message) {
                $this->prepend(Html::tag('p', ['class' => 'state-hint error'], $message));
            }
        }
    }

    public function addHint($hint)
    {
        return $this->add(Html::tag('p', ['class' => 'information'], $hint));
    }

    public function optionalEnum($enum, $nullLabel = null)
    {
        if ($nullLabel === null) {
            $nullLabel = $this->translate('- please choose -');
        }

        return [null => $nullLabel] + $enum;
    }
}
