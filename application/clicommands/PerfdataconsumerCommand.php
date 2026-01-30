<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Exception;
use gipfl\Translation\StaticTranslator;
use gipfl\Web\Form;
use gipfl\ZfDbStore\ZfDbStore;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Data\ResourceFactory;
use Icinga\Module\Vspheredb\Web\Form\PerfdataConsumerForm;

class PerfdataconsumerCommand extends Command
{
    /**
     * Configure a Performance Data Consumer
     *
     * USAGE
     *
     * icingacli vspheredb perfdataconsumer create <name> --implementation <name> [--disabled] [--other <settings>]
     *
     * @return void
     */
    public function createAction(): void
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
            'implementation' => $implementation,
            'submit'         => 'Create'
        ] + $this->params->getParams();
        if ($this->submitForm($params)) {
            echo "'$name' has been created\n";
            exit(0);
        }

        $this->fail("Creating '$name' failed for unknown reasons");
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    protected function submitForm(array $params): bool
    {
        StaticTranslator::setNoTranslator();
        $form = new PerfdataConsumerForm($this->loop(), $this->remoteClient(), $this->getStore());
        $form->disableCsrf()->doNotCheckFormName();

        return $this->validateRequestWithForm(
            (new ServerRequest('POST', 'cli'))->withParsedBody($params),
            $form
        );
    }

    /**
     * @param ServerRequest $request
     * @param Form          $form
     *
     * @return bool
     */
    protected function validateRequestWithForm(ServerRequest $request, Form $form): bool
    {
        $success = false;
        $form->on($form::ON_SUBMIT, function () use (&$success) {
            $success = true;
        });
        $form->handleRequest($request);
        if (! $form->isValid()) {
            foreach ($form->getElements() as $element) {
                if (! $element->isValid()) {
                    foreach ($element->getMessages() as $message) {
                        $this->fail(sprintf('--%s: %s', $element->getName(), $this->wantErrorMessage($message)));
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
            $this->fail($this->wantErrorMessage($message));
        }

        return $success;
    }

    /**
     * @param Exception|string $message
     *
     * @return string
     */
    protected function wantErrorMessage(Exception|string $message): string
    {
        if ($message instanceof Exception) {
            return $message->getMessage();
        }

        return $message;
    }

    /**
     * @return ZfDbStore
     */
    protected function getStore(): ZfDbStore
    {
        $connection = ResourceFactory::create($this->Config()->get('db', 'resource'));

        return new ZfDbStore($connection->getDbAdapter());
    }
}
