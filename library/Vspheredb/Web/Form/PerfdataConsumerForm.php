<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Json\JsonString;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use gipfl\Web\Form\Decorator\DdDtDecorator;
use gipfl\ZfDbStore\Store;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use Icinga\Module\Vspheredb\Hook\PerfDataConsumerHook;
use Icinga\Module\Vspheredb\Storable\PerfdataConsumer;
use ipl\Html\FormElement\SubmitElement;
use React\EventLoop\LoopInterface;

class PerfdataConsumerForm extends ObjectForm
{
    const ON_DELETE = 'delete';

    use TranslationHelper;

    protected $class = PerfdataConsumer::class;
    /**
     * @var RemoteClient
     */
    protected $client;
    /**
     * @var LoopInterface
     */
    protected $loop;

    public function __construct(LoopInterface $loop, RemoteClient $client, Store $store)
    {
        $this->loop = $loop;
        $this->client = $client;
        parent::__construct($store);
    }

    public function assemble()
    {
        $this->addElement('text', 'name', [
            'label'       => $this->translate('Name'),
            'required'    => true,
            'description' => $this->translate('Arbitrary unique name for this Performance Data Consumer'),
        ]);
        $this->addElement('boolean', 'enabled', [
            'label' => $this->translate('Enabled'),
            'value' => 'y',
            'required' => true,
        ]);
        if ($this->object instanceof PerfdataConsumer) {
            $this->populate((array) $this->object->settings());
        }
        if ($implementation = $this->selectImplementation()) {
            $this->addImplementation($implementation);
        }
        // TODO: web 2.9 broke Multiselect
        // $this->addPerformanceSets();
        $this->addButtons($implementation);
    }

    public function isValidEvent($event)
    {
        if ($event === self::ON_DELETE) {
            return true;
        }

        return parent::isValidEvent($event);
    }

    public function getValues()
    {
        $values = parent::getValues();
        $mainProperties = [
            'implementation',
            'name',
            'settings',
            'enabled',
        ];
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

    protected function selectImplementation()
    {
        if (! $this->isNew()) {
            return $this->object->get('implementation');
        }
        $this->addElement('select', 'implementation', [
            'label'    => $this->translate('Implementation'),
            'options'  => [
                    null => $this->translate('- please choose -'),
                ] + PerfDataConsumerHook::enum(),
            'required' => true,
            'class'    => 'autosubmit',
        ]);

        return $this->getValue('implementation');
    }

    protected function addImplementation($implementation)
    {
        /** @var PerfDataConsumerHook $instance */
        $instance = new $implementation;
        $instance->setLoop($this->loop);
        $form = $instance->getConfigurationForm($this->client);
        if ($form instanceof Form) {
            $form->disableCsrf();
            $form->doNotCheckFormName();
        }
        $form->populate($this->getSentValues());
        // TODO: Clone Request, strip our values from request?
        $form->handleRequest($this->getRequest());
        $form->ensureAssembled();
        $this->add($form->getContent());
        foreach ($form->getElements() as $element) {
            $this->registerElement($element);
        }
    }

    protected function addButtons($implementation)
    {
        if ($implementation) {
            $submit = new SubmitElement('submit', [
                'label' => $this->isNew() ? $this->translate('Create') : $this->translate('Store')
            ]);
            $this->addElement($submit);
            $this->setSubmitButton($submit);
            $deco = $submit->getWrapper();
            assert($deco instanceof DdDtDecorator);

            if ($this->isNew()) {
                $back = new SubmitElement('btn_back', [
                    'label' => $this->translate('Back')
                ]);
                $deco->dd()->add($back);
                $this->registerElement($back);
                if ($back->hasBeenPressed()) {
                    $this->setElementValue('implementation', null);
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
            $this->addElement('submit', 'submit', [
                'label' => $this->translate('Next')
            ]);
        }
        /*
        $this->addElement('submit', 'btn_delete', [
            'label' => $this->translate('Delete')
        ]);
        $deleteButton = $this->getElement('btn_delete');
        if ($deleteButton && $deleteButton->hasBeenPressed()) {
            $this->getObject()->delete();
            $this->deleted = true;
        }
        */
    }
}
