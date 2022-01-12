<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
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

    use FormElementStealer;
    use TranslationHelper;

    protected $class = PerfdataConsumer::class;

    /** @var RemoteClient */
    protected $client;

    /** @var LoopInterface */
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
        if ($this->object instanceof PerfdataConsumer && !$this->hasBeenSent()) {
            $this->populate((array) $this->object->settings());
        }
        if ($implementation = $this->selectImplementation()) {
            $this->addImplementation($implementation);
        }
        // TODO: web 2.9 broke Multiselect for nested options
        // $this->addPerformanceSets();
        $this->addButtons(isset($implementation), 'implementation');
    }

    public function isValidEvent($event)
    {
        if ($event === self::ON_DELETE) {
            return true;
        }

        return parent::isValidEvent($event);
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
        $class = PerfDataConsumerHook::getClass($implementation);
        if (! $class || ! class_exists($class)) {
            $this->triggerElementError('implementation', sprintf(
                $this->translate('There is no such PerfdataConsumer: %s'),
                $implementation
            ));
            return;
        }
        $instance = new $class;
        $instance->setLoop($this->loop);
        $this->addFormElementsFrom($instance->getConfigurationForm($this->client));
    }
}
