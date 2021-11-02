<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use ipl\Html\FormElement\SubmitElement;

abstract class InlineForm extends Form
{
    use TranslationHelper;

    /** @var boolean|null */
    protected $hasBeenSubmitted;

    public function __construct()
    {
        $this->setMethod('POST');
        $this->addAttributes(['class' => 'inline']);
    }

    protected function setupStyling()
    {
        $this->setSeparator("\n");
        $this->addAttributes(['class' => 'gipfl-form']);
        // Avoiding default decorator
    }

    protected function provideAction($label, $title = null)
    {
        $next = new SubmitElement('next', [
            'class' => 'link-button',
            'label' => sprintf('[ %s ]', $label),
            'title' => $title,
        ]);
        $submit = new SubmitElement('submit', [
            'class' => 'link-button',
            'label' => sprintf(
                '[ ' . $this->translate('Really %s') . ' ]',
                $label
            )
        ]);
        $cancel = new SubmitElement('cancel', [
            'class' => 'link-button',
            'label' => '[ ' . $this->translate('Cancel') . ' ]'
        ]);
        $this->toggleNextSubmitCancel($next, $submit, $cancel);
    }

    public function setSubmitted($submitted = true)
    {
        $this->hasBeenSubmitted = (bool) $submitted;

        return $this;
    }

    public function hasBeenSubmitted()
    {
        if ($this->hasBeenSubmitted === null) {
            return parent::hasBeenSubmitted();
        } else {
            return $this->hasBeenSubmitted;
        }
    }

    protected function toggleNextSubmitCancel(
        SubmitElement $next,
        SubmitElement $submit,
        SubmitElement $cancel
    ) {
        if ($this->hasBeenSent()) {
            $this->addElement($submit);
            $this->addElement($cancel);
            if ($cancel->hasBeenPressed()) {
                // HINT: we might also want to redirect on cancel and stop here,
                //       but currently we have no Response
                $this->setSubmitted(false);
                $this->remove($submit);
                $this->remove($cancel);
                $this->add($next);
                $this->setSubmitButton($next);
            } else {
                $this->setSubmitButton($submit);
                $this->remove($next);
            }
        } else {
            $this->addElement($next);
        }
    }
}
