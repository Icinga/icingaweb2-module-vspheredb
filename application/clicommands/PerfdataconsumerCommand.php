<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use gipfl\ZfDbStore\ZfDbStore;
use Icinga\Data\ResourceFactory;
use Icinga\Module\Vspheredb\ProvidedHook\Vspheredb\PerfDataConsumerInfluxDb;
use Icinga\Module\Vspheredb\Web\Form\PerfdataConsumerForm;
use RingCentral\Psr7\ServerRequest;

class PerfdataconsumerCommand extends Command
{
    /**
     * Configure a Performance Data Consumer
     *
     * USAGE
     *
     * icingacli vspheredb perfdataconsumer create <name> --implementation <name> [--disabled] [--other <settings>]
     */
    public function createAction()
    {
        $name = $this->params->shift();
        if (strlen($name) === 0) {
            $this->fail('<name> is required');
        }
        try {
            $implementation = $this->params->shiftRequired('implementation', true);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
        $enabled = !$this->params->shift('disabled');
        $params = [
            'name'           => $name,
            'enabled'        => $enabled ? 'y' : 'n',
            'implementation' => PerfDataConsumerInfluxDb::class, // $implementation,
            'submit'         => 'Create',
        ] + $this->params->getParams();
        if ($this->submitForm($params)) {
            echo "'$name' has been created\n";
            exit(0);
        }

        $this->fail("Creating '$name' failed for unknown reasons");
    }

    protected function submitForm($params)
    {
        TranslationHelper::setNoTranslator();
        $form = new PerfdataConsumerForm($this->loop(), $this->remoteClient(), $this->getStore());
        $form->disableCsrf()->doNotCheckFormName();

        return $this->validateRequestWithForm(
            (new ServerRequest('POST', 'cli'))->withParsedBody($params),
            $form
        );
    }

    protected function validateRequestWithForm(ServerRequest $request, Form $form)
    {
        $success = false;
        $form->on($form::ON_SUCCESS, function () use (&$success) {
            $success = true;
        });
        $form->handleRequest($request);
        if (! $form->isValid()) {
            foreach ($form->getElements() as $element) {
                if (! $element->isValid()) {
                    foreach ($element->getMessages() as $message) {
                        $this->failForMessage($message);
                    }
                    $this->fail(sprintf('--%s is not valid', $element->getName()));
                }
                if ($element->isRequired() && $element->getValue() === null) {
                    $this->fail(sprintf('--%s <value> is required', $element->getName()));
                }
            }
            $this->fail('Validation failed for unknown reasons');
        }
        foreach ($form->getMessages() as $message) {
            $this->failForMessage($message);
        }

        return $success;
    }

    protected function failForMessage($message)
    {
        if ($message instanceof \Exception) {
            $this->fail($message->getMessage());
        } else {
            $this->fail($message);
        }
    }

    protected function getStore()
    {
        $connection = ResourceFactory::create($this->Config()->get('db', 'resource'));
        return new ZfDbStore($connection->getDbAdapter());
    }
}
