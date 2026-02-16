<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use Exception;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use gipfl\Web\Form\Element\TextWithActionButton;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use ipl\Html\Attributes;
use ipl\Html\FormElement\SelectElement;
use React\EventLoop\LoopInterface;

use function React\Async\await;
use function React\Promise\Timer\timeout;

class InfluxDbConnectionForm extends Form
{
    use TranslationHelper;

    public const INFLUXDB_MIN_SUPPORTED_VERSION = '1.6.0';

    /** @var LoopInterface */
    protected LoopInterface $loop;

    protected ?string $detectedApiVersion = null;

    protected ?string $influxDbVersion = null;

    protected ?TextWithActionButton $baseUrlElement = null;

    /** @var RemoteClient */
    protected RemoteClient $client;

    protected bool $checkedNow = false;

    public function __construct(LoopInterface $loop, RemoteClient $client)
    {
        $this->loop = $loop;
        $this->client = $client;
    }

    protected function assemble(): void
    {
        $this->addHidden('checked_url', ['ignore' => true]);
        $this->addHidden('checked_api_version', ['ignore' => true]);
        $this->baseUrlElement = new TextWithActionButton('base_url', [
            'label'       => $this->translate('Base URL'),
            'description' => $this->translate('InfluxDB base URL, like http://influxdb.example.com:8086'),
            'required'    => true
        ], [
            'label' => $this->translate('Verify'),
            'title' => $this->translate('Attempt to establish a connection to your InfluxDB instance')
        ]);
        $this->baseUrlElement->addToForm($this);
        $this->addElement('select', 'api_version', [
            'label' => $this->translate('API Version'),
            'class' => 'autosubmit',
            'description' => $this->translate('InfluxDB API version, autodetect should work fine'),
            'options' => [
                '' => $this->translate('Autodetect'),
                'v1' => 'v1',
                'v2' => 'v2'
            ]
        ]);
        $this->appendVersionInformation($this->getDetectedApiVersion(), $this->getInfluxDbVersion());
        $this->addCredentials();
    }

    protected function addCredentials(): static
    {
        if ($this->getApiVersion() === 'v2') {
            $this->addV2Credentials();
        } else {
            $this->addV1Credentials();
        }
        $this->validateCredentials();

        return $this;
    }

    protected function validateCredentials(): void
    {
        if (! $this->checkedNow) {
            return;
        }
        $username = $this->getValue('username');
        if (empty($username)) {
            return;
        }
        try {
            if (
                $this->tryCredentials(
                    $this->getValue('base_url'),
                    $this->getValue('apiVersion'),
                    $username,
                    $this->getValue('password')
                )
            ) {
                $this->getElement('password')->getAttributes()->add('class', 'validated');
            }
        } catch (Exception $e) {
            $this->getElement('password')->addMessage($this->getExceptionMessageWithoutPhpFile($e));
        }
    }

    protected function getExceptionMessageWithoutPhpFile(Exception $e): string
    {
        return preg_replace('/\sin\s.+?\.php\(\d+\)/', '', $e->getMessage());
    }

    protected function remoteRequest(string $request, array $params = []): mixed
    {
        return await(timeout($this->client->request($request, $params), 5, $this->loop));
    }

    protected function getDetectedApiVersion(): ?string
    {
        return $this->detectedApiVersion ??= $this->getApiVersionForVersionString($this->getInfluxDbVersion());
    }

    protected function getApiVersion(): string
    {
        return $this->getValue('api_version', $this->getDetectedApiVersion());
    }

    protected function getInfluxDbVersion(): ?string
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
     * @return TextWithActionButton|null
     */
    protected function getUrlElement(): ?TextWithActionButton
    {
        return $this->baseUrlElement;
    }

    protected function markUrlAsValidated(): static
    {
        $this->getUrlElement()->getElement()->addAttributes(Attributes::create(['class' => 'validated']));

        return $this;
    }

    protected function autodetectIsUpToDate(): bool
    {
        $baseUrl = $this->getValue('base_url');

        return $baseUrl && $this->getValue('checked_url') === $baseUrl;
    }

    protected function appendVersionInformation(?string $apiVersion, ?string $detectedVersion): void
    {
        if (empty($apiVersion) || empty($detectedVersion)) {
            return;
        }
        $element = $this->getElement('api_version');
        assert($element instanceof SelectElement);
        $autoOption = $element->getOption(null);
        $autoOption->setLabel(
            sprintf($this->translate('Autodetect: %s API, Version is %s'), $apiVersion, $detectedVersion)
        );
        $selectedOption = $element->getOption($apiVersion);
        $selectedOption->setLabel(sprintf($this->translate('%s (detected %s)'), $apiVersion, $detectedVersion));
        // $element->setValue($apiVersion);
    }

    protected function addV1Credentials(): void
    {
        $this->addElement('text', 'username', ['label' => $this->translate('Username')]);
        $this->addElement('password', 'password', [
            'label'    => $this->translate('Password'),
            'required' => $this->hasElementValue('username')
        ]);
    }

    protected function addV2Credentials(): void
    {
        $this->addElement('text', 'username', [
            'label'    => $this->translate('Organisation'),
            'required' => true
        ]);
        $this->addElement('text', 'password', [
            'label'    => $this->translate('Token'),
            // 'description' => $this->translate('InfluxDB Token (InfluxDB -> Data -> Tokens'),
            'required' => true
        ]);
    }

    protected function detectInfluxDbVersion($baseUrl): false|string|null
    {
        if ($this->getValue('base_url') === null) {
            return null;
        }
        try {
            $version = $this->remoteRequest('influxdb.discoverVersion', ['baseUrl' => $baseUrl]);
            $version = ltrim($version, 'v');
            if ($this->versionIsFine($version)) {
                $this->checkedNow = true;
                $this->setCheckedApiVersionFor($baseUrl, $version);
                $this->markUrlAsValidated();

                return $version;
            }

            throw new Exception("Version $version is not supported");
        } catch (Exception $e) {
            $this->triggerElementError('base_url', $e->getMessage());

            return false;
        }
    }

    protected function tryCredentials(string $baseUrl, string $apiVersion, string $username, string $password): mixed
    {
        return $this->remoteRequest('influxdb.testConnection', [
            'baseUrl'    => $baseUrl,
            'apiVersion' => $apiVersion,
            'username'   => $username,
            'password'   => $password
        ]);
    }

    protected function setCheckedApiVersionFor(string $baseUrl, string $version): void
    {
        $this->getElement('checked_url')->setValue($baseUrl);
        $this->setElementValue('checked_api_version', $version);
    }

    protected function versionIsFine(string $version): bool
    {
        return version_compare($version, static::INFLUXDB_MIN_SUPPORTED_VERSION, 'ge');
    }

    protected function getApiVersionForVersionString(?string $version): ?string
    {
        if ($version === null) {
            return null;
        }

        return version_compare($version, '1.999.999', 'gt') ? 'v2' : 'v1';
    }
}
