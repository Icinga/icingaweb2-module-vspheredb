<?php

namespace Icinga\Module\Vspheredb\Polling;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use gipfl\Curl\CurlAsync;
use gipfl\Log\PrefixLogger;
use Icinga\Module\Vspheredb\Daemon\StateMachine;
use Icinga\Module\Vspheredb\MappedClass\UserSession;
use Icinga\Module\Vspheredb\SafeCacheDir;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;

class ApiConnection implements EventEmitterInterface
{
    use EventEmitterTrait;
    use StateMachine;

    // Events
    public const ON_READY = 'ready';

    public const ON_ERROR = 'error';

    // States
    public const STATE_STOPPED = 'stopped';

    public const STATE_STOPPING = 'stopping';

    public const STATE_INIT = 'initializing';

    public const STATE_LOGIN = 'login';

    public const STATE_CONNECTED = 'connected';

    public const STATE_FAILING = 'failing';

    /** @var CurlAsync */
    protected CurlAsync $curl;

    /** @var ?LoopInterface */
    protected ?LoopInterface $loop = null;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var ?TimerInterface */
    protected ?TimerInterface $scheduledPollerStartup = null;

    /** @var ServerInfo */
    protected ServerInfo $serverInfo;

    /** @var ?string */
    protected ?string $wsdlFile = null;

    /** @var ?VsphereApi */
    protected ?VsphereApi $api = null;

    /** @var ?bool */
    protected ?bool $stopping = null;

    /** @var ?PromiseInterface */
    protected ?PromiseInterface $loginPromise = null;

    /** @var ?PromiseInterface */
    protected ?PromiseInterface $wsdlPromise = null;

    /** @var ?TimerInterface */
    protected ?TimerInterface $sessionChecker = null;

    /** @var ?string */
    protected ?string $lastErrorMessage = null;

    public function __construct(CurlAsync $curl, ServerInfo $serverInfo, LoggerInterface $logger)
    {
        $this->curl = $curl;
        $this->serverInfo = $serverInfo;
        $this->logger = new PrefixLogger(sprintf(
            '[api %s (id=%d)] ',
            $serverInfo->get('host'),
            $serverInfo->get('id')
        ), $logger);
        $this->initializeStateMachine(self::STATE_STOPPED);
        $this->onTransition([self::STATE_STOPPED, self::STATE_FAILING], self::STATE_INIT, function () {
            $this->eventuallyRemoveScheduledAttempt();
            $this->startWsdlDownload();
        });
        $this->onTransition(self::STATE_INIT, self::STATE_LOGIN, function () {
            $this->eventuallyLogout()->then(function () {
                $this->login();
            });
        });
        $this->onTransition(self::STATE_LOGIN, self::STATE_CONNECTED, function () {
            $this->runSessionChecker();
            $this->emit(self::ON_READY, [$this]);
        });
        $this->onTransition(self::STATE_INIT, self::STATE_STOPPING, function () {
            $this->stopping = true;
            if ($this->wsdlPromise) {
                $this->wsdlPromise->cancel();
                $this->wsdlPromise = null;
            }
            $this->setState(self::STATE_STOPPED);
        });
        $this->onTransition(self::STATE_LOGIN, self::STATE_STOPPING, function () {
            $this->stopping = true;
            if ($this->loginPromise) {
                $this->loginPromise->cancel();
                $this->loginPromise = null;
            }
            $this->setState(self::STATE_STOPPED);
        });
        // TODO: do we need failing -> stopping?
        $this->onTransition(self::STATE_CONNECTED, self::STATE_STOPPING, function () {
            $this->stopping = true;
            $this->logger->debug('Logging out');
            $this->stopSessionChecker();
            $done = function () {
                $this->setState(self::STATE_STOPPED);
            };
            $this->api->logout()->then($done, $done);
        });
        $this->onTransition(self::STATE_STOPPING, self::STATE_STOPPED, function () {
            $this->stopping = false;
        });
        $this->onTransition(self::STATE_INIT, self::STATE_FAILING, function () {
            $this->emit(self::ON_ERROR, [$this]);
        });
        $this->onTransition(self::STATE_LOGIN, self::STATE_FAILING, function () {
            $this->emit(self::ON_ERROR, [$this]);
        });
        $this->onTransition(self::STATE_CONNECTED, self::STATE_FAILING, function () {
            $this->loop->futureTick(function () {
                $this->stopSessionChecker();
                $this->scheduleNextAttempt();
                $this->emit(self::ON_ERROR, [$this]);
            });
        });
    }

    protected function stopSessionChecker(): void
    {
        $this->loop->cancelTimer($this->sessionChecker);
        $this->sessionChecker = null;
    }

    protected function runSessionChecker(): void
    {
        $this->sessionChecker = $this->loop->addPeriodicTimer(150, function () {
            $this->getApi()->eventuallyLogin()->then(null, function (Exception $e) {
                $this->logError('Login failed: ' . $e->getMessage());
                $this->loop->futureTick(function () {
                    $this->setState(self::STATE_FAILING);
                });
            });
        });
    }

    public function getApi(): ?VsphereApi
    {
        return $this->api;
    }

    public function isReady(): bool
    {
        return $this->getState() === self::STATE_CONNECTED;
    }

    public function getServerInfo(): ServerInfo
    {
        return $this->serverInfo;
    }

    protected function scheduleNextAttempt($delay = 60): void
    {
        if ($this->scheduledPollerStartup) {
            return;
        }
        $this->logger->info("Will try to reconnect in $delay seconds");
        $this->scheduledPollerStartup = $this->loop->addTimer($delay, function () {
            $this->setState(self::STATE_INIT);
        });
    }

    protected function eventuallyRemoveScheduledAttempt(): void
    {
        if ($this->scheduledPollerStartup) {
            $this->loop->cancelTimer($this->scheduledPollerStartup);
            $this->scheduledPollerStartup = null;
        }
    }

    protected function startWsdlDownload(): void
    {
        $this->wsdlPromise = $this->fetchWsdl()
            ->then(function ($wsdlFile) {
                // might be from cache
                $this->wsdlFile = $wsdlFile;
                $this->wsdlPromise = null;
                $this->setState(self::STATE_LOGIN);
            }, function () {
                $this->wsdlPromise = null;
                if (! $this->stopping) {
                    $this->logError('WSDL download failed');
                    $this->setState(self::STATE_FAILING);
                }
            });
    }

    protected function eventuallyLogout(): PromiseInterface
    {
        $api = new VsphereApi($this->wsdlFile, $this->serverInfo, $this->curl, $this->loop, $this->logger);
        return $api->eventuallyLogout();
    }

    protected function login(): PromiseInterface
    {
        $api = new VsphereApi($this->wsdlFile, $this->serverInfo, $this->curl, $this->loop, $this->logger);
        return $this->loginPromise = $api->eventuallyLogin()->then(function (UserSession $session) use ($api) {
            $this->api = $api;
            $this->loginPromise = null;
            $this->setState(self::STATE_CONNECTED);
            $this->lastErrorMessage = null;
        }, function (Exception $e) {
            $this->loginPromise = null;
            if (! $this->stopping) {
                $this->logError('Login failed: ' . $e->getMessage());
                $this->setState(self::STATE_FAILING);
            }
            throw $e;
        });
    }

    public function stop(): void
    {
        $this->setState(self::STATE_STOPPING);
    }

    public function run(LoopInterface $loop): void
    {
        $this->loop = $loop;
        $this->setState(self::STATE_INIT);
    }

    public function fetchWsdl(): PromiseInterface
    {
        $serverId = $this->serverInfo->getServerId();
        $cacheDir = SafeCacheDir::getSubDirectory("wsdl-$serverId");
        $loader = new WsdlLoader($cacheDir, $this->logger, $this->serverInfo, $this->curl);
        return $loader->fetchInitialWsdlFile($this->loop);
    }

    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    protected function logError($message): void
    {
        $this->lastErrorMessage = $message;
        $this->logger->error($message);
    }
}
