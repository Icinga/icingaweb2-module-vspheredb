<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use Exception;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use gipfl\Web\Form\Element\TextWithActionButton;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use Icinga\Module\Vspheredb\Hook\PerfDataConsumerHook;
use Icinga\Web\Notification;
use ipl\Html\FormElement\BaseFormElement;
use ipl\Html\FormElement\SelectElement;
use React\EventLoop\LoopInterface;

use function React\Async\await;
use function React\Promise\Timer\timeout;

class ChooseInfluxDatabaseForm extends Form
{
    use TranslationHelper;

    /** @var LoopInterface */
    protected LoopInterface $loop;

    /** @var array|null|false */
    protected array|null|false $dbList = null;

    /** @var RemoteClient */
    protected RemoteClient $client;

    /** @var PerfDataConsumerHook */
    protected PerfDataConsumerHook $hook;

    public function __construct(LoopInterface $loop, RemoteClient $client, PerfDataConsumerHook $hook)
    {
        $this->loop = $loop;
        $this->client = $client;
        $this->hook = $hook;
    }

    protected function assemble(): void
    {
        $this->addDbSelection();
    }

    protected function prepareParams(): array
    {
        return [
            'baseUrl'    => $this->hook->getSetting('base_url'),
            'apiVersion' => $this->hook->getSetting('api_version'),
            'username'   => $this->hook->getSetting('username'),
            'password'   => $this->hook->getSetting('password')
        ];
    }

    protected function getDbList(): false|array|null
    {
        if ($this->dbList === null) {
            $this->refreshDbList();
        }

        return $this->dbList;
    }

    protected function remoteRequest(string $request, ?array $params = []): mixed
    {
        return await(timeout($this->client->request($request, $params), 5, $this->loop));
    }

    protected function refreshDbList(): void
    {
        try {
            $this->dbList = array_filter(
                (array) $this->remoteRequest('influxdb.listDatabases', $this->prepareParams()),
                fn ($value) => $value[0] !== '_'
            );
        } catch (Exception) {
            // Hint: we no longer refresh if it's false
            $this->dbList = false;
        }
    }

    protected function createDatabase($name): mixed
    {
        Notification::info("Creating $name");
        $promise = $this->client->request('influxdb.createDatabase', $this->prepareParams() + ['dbName' => $name]);
        $result = await($promise);
        Notification::info("DON $name");

        return $result;
    }

    protected function createRequestedDb(BaseFormElement $element, TextWithActionButton $action): void
    {
        $name = $element->getValue();
        try {
            $this->createDatabase($name);
            $this->remove($action->getButton());
            $this->remove($action->getElement());
            $this->refreshDbList();
            $dbOptions = $this->getDbOptions();
            if ($element instanceof SelectElement) {
                $element->setOptions($dbOptions);
            }
            if (in_array($name, $dbOptions)) {
                $element->setValue($name);
            } else {
                $this->triggerElementError(
                    $element->getName(),
                    $this->translate('There is no such DB/Bucket: "%s"'),
                    $name
                );
            }
        } catch (Exception $e) {
            $element->addMessage($e->getMessage());
        }
    }

    protected function getDbOptions(): array
    {
        return ['' => $this->translate('Please choose')]
        + array_combine($this->dbList, $this->dbList)
        + ['_new' => ' -> ' . $this->translate('Create a new Database')];
    }

    protected function addDbSelection(): static
    {
        if ($this->getSentValue('dbname') === '_new') {
            $elDbName = $this->createElement('hidden', 'dbname');
            $this->registerElement($elDbName);
            $this->prepend($elDbName);
        } elseif ($this->getDbList()) {
            $elDbName = $this->createElement('select', 'dbname', [
                'label'       => $this->translate('Database'),
                // 'description' => $this->translate('InfluxDB database name'),
                'class'       => 'autosubmit',
                'options'     => $this->getDbOptions()
            ]);
            $this->addElement($elDbName);
        } else {
            $elDbName = $this->createElement('text', 'dbname', [
                'label'       => $this->translate('Database'),
                'required'    => true
            ]);
            $this->addElement($elDbName);
        }
        if ($this->getValue('dbname') === '_new') {
            $action = new TextWithActionButton('new_dbname', [
                'label'       => $this->translate('New Database'),
                'description' => $this->translate('New InfluxDB database name'),
                'required'    => true
            ], [
                'label' => $this->translate('Create'),
                'title' => $this->translate('Create a new InfluxDB database')
            ]);
            $action->addToForm($this);
            if ($action->getButton()->hasBeenPressed()) {
                assert($elDbName instanceof BaseFormElement);
                $this->createRequestedDb($elDbName, $action);
            }
        }

        return $this;
    }
}
