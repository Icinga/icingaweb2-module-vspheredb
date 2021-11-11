<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use gipfl\Web\Form\Element\TextWithActionButton;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use ipl\Html\FormElement\SelectElement;
use React\EventLoop\LoopInterface;
use function Clue\React\Block\await;

class InfluxDbConnectionForm extends Form
{
    use TranslationHelper;

    const INFLUXDB_MIN_SUPPORTED_VERSION = '1.6.0';

    /** @var LoopInterface */
    protected $loop;

    protected $detectedApiVersion;

    protected $influxDbVersion;

    protected $baseUrlElement;
    /**
     * @var RemoteClient
     */
    protected $client;

    public function __construct(LoopInterface $loop, RemoteClient $client)
    {
        $this->loop = $loop;
        $this->client = $client;
    }

    public function assemble()
    {
        $this->addHidden('checked_url', ['ignore' => true]);
        $this->addHidden('checked_api_version', ['ignore' => true]);
        $this->baseUrlElement = new TextWithActionButton('base_url', [
            'label'       => $this->translate('Base URL'),
            'description' => $this->translate('InfluxDB base URL, like http://influxdb.example.com:8086'),
            'required'    => true,
        ], [
            'label' => $this->translate('Verify'),
            'title' => $this->translate('Attempt to establish a connection to your InfluxDB instance')
        ]);
        $this->baseUrlElement->addToForm($this);
        $this->addElement('select', 'api_version', [
            'label' => $this->translate('API Version'),
            'class' => 'autosubmit',
            'description' => $this->translate(
                'InfluxDB API version, autodetect should work fine'
            ),
            'options' => [
                null => $this->translate('Autodetect'),
                'v1' => 'v1',
                'v2' => 'v2',
            ],
        ]);
        $this->appendVersionInformation($this->getDetectedApiVersion(), $this->getInfluxDbVersion());
        $this->addCredentials();
    }

    protected function addCredentials()
    {
        if ($this->getApiVersion() === 'v2') {
            $this->addV2Credentials();
        } else {
            $this->addV1Credentials();
        }

        return $this;
    }

    protected function prepareParams()
    {
        $params = [
            'baseUrl' => $this->getValue('base_url'),
        ];
        switch ($this->getApiVersion()) {
            case 'v1':
                $params['apiVersion'] = 'v1';
                $params['username'] = $this->getValue('username');
                $params['password'] = $this->getValue('password');
                break;
            case 'v2':
                $params['apiVersion'] = 'v2';
                $params['org'] = $this->getValue('org');
                $params['token'] = $this->getValue('token');
                break;
        }

        return $params;
    }

    protected function remoteRequest($request, $params = [])
    {
        return await($this->client->request($request, $params), $this->loop, 5);
    }

    protected function getDetectedApiVersion()
    {
        if ($this->detectedApiVersion === null) {
            $this->detectedApiVersion = $this->getApiVersionForVersionString(
                $this->getInfluxDbVersion()
            );
        }

        return $this->detectedApiVersion;
    }

    protected function getApiVersion()
    {
        return $this->getValue('api_version', $this->getDetectedApiVersion());
    }

    protected function getInfluxDbVersion()
    {
        if ($this->influxDbVersion === null) {
            $element = $this->getUrlElement();
            if ($element->getButton()->hasBeenPressed()) {
                $this->influxDbVersion = $this->detectInfluxDbVersion($this->getValue('base_url'));
            } elseif ($this->autodetectIsUpToDate()) {
                $this->markUrlAsValidated();
                $this->influxDbVersion = $this->getValue('checked_api_version');
            }
        }

        return $this->influxDbVersion;
    }

    /**
     * @return TextWithActionButton
     */
    protected function getUrlElement()
    {
        return $this->baseUrlElement;
    }

    protected function markUrlAsValidated()
    {
        $this
            ->getUrlElement()
            ->getElement()
            ->addAttributes(['class' => 'validated']);

        return $this;
    }

    protected function autodetectIsUpToDate()
    {
        $baseUrl = $this->getValue('base_url');

        return $baseUrl && $this->getValue('checked_url') === $baseUrl;
    }

    protected function appendVersionInformation($apiVersion, $detectedVersion)
    {
        if (empty($apiVersion) || empty($detectedVersion)) {
            return;
        }
        $element = $this->getElement('api_version');
        assert($element instanceof SelectElement);
        $autoOption = $element->getOption(null);
        $autoOption->setLabel(\sprintf(
            $this->translate('Autodetect: %s API, Version is %s'),
            $apiVersion,
            $detectedVersion
        ));
        $selectedOption = $element->getOption($apiVersion);
        $selectedOption->setLabel(\sprintf(
            $this->translate('%s (detected %s)'),
            $apiVersion,
            $detectedVersion
        ));
       // $element->setValue($apiVersion);
    }

    protected function addV1Credentials()
    {
        $this->addElement('text', 'username', [
            'label'       => $this->translate('Username'),
        ]);
        $this->addElement('password', 'password', [
            'label'       => $this->translate('Password'),
            'required'    => $this->hasElementValue('username'),
        ]);
    }

    protected function addV2Credentials()
    {
        $this->addElement('text', 'token', [
            'label'       => $this->translate('Token'),
            // 'description' => $this->translate('InfluxDB Token (InfluxDB -> Data -> Tokens'),
            'required'    => true,
        ]);
        $this->addElement('text', 'org', [
            'label'       => $this->translate('Organisation'),
            'required'    => true,
        ]);
    }

    protected function detectInfluxDbVersion($baseUrl)
    {
        if ($this->getValue('base_url') === null) {
            return null;
        }
        try {
            $version = $this->remoteRequest('influxdb.discoverVersion', [
                'baseUrl' => $baseUrl,
            ]);
            if ($this->versionIsFine($version)) {
                $this->setCheckedApiVersionFor($baseUrl, $version);
                $this->markUrlAsValidated();
                return $version;
            } else {
                throw new \Exception("Version $version is not supported");
            }
        } catch (\Exception $e) {
            $this->triggerElementError('base_url', $e->getMessage());
            return false;
        }
    }

    protected function setCheckedApiVersionFor($baseUrl, $version)
    {
        $this->getElement('checked_url')->setValue($baseUrl);
        $this->setElementValue('checked_api_version', $version);
    }

    protected function versionIsFine($version)
    {
        return \version_compare($version, static::INFLUXDB_MIN_SUPPORTED_VERSION, 'ge');
    }

    protected function getApiVersionForVersionString($version)
    {
        if ($version === null) {
            return null;
        }

        return \version_compare($version, '1.999.999', 'gt') ? 'v2' : 'v1';
    }
}
