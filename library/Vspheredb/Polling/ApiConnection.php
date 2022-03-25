<?php

namespace Icinga\Module\Vspheredb\Polling;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use gipfl\Curl\CurlAsync;
use gipfl\Log\PrefixLogger;
use gipfl\ReactUtils\RetryUnless;
use Icinga\Module\Vspheredb\Daemon\StateMachine;
use Icinga\Module\Vspheredb\MappedClass\UserSession;
use Icinga\Module\Vspheredb\SafeCacheDir;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\ExtendedPromiseInterface;

class ApiConnection implements EventEmitterInterface
{
    use EventEmitterTrait;
    use StateMachine;

    const ON_READY = 'ready';
    const ON_ERROR = 'error';

    const STATE_STOPPED = 'stopped';
    const STATE_STOPPING = 'stopping';
    const STATE_INIT = 'initializing';
    const STATE_LOGIN = 'login';
    const STATE_CONNECTED = 'connected';
    const STATE_FAILING = 'failing';

    /** @var CurlAsync */
    protected $curl;

    /** @var LoopInterface */
    protected $loop;

    /** @var LoggerInterface */
    protected $logger;

    protected $scheduledPollerStartup;

    /** @var ServerInfo */
    protected $serverInfo;

    protected $wsdlFile;

    /** @var VsphereApi */
    protected $api;

    protected $stopping;

    /** @var ExtendedPromiseInterface */
    protected $loginPromise;

    /** @var RetryUnless */
    protected $wsdlPromise;

    /** @var TimerInterface */
    protected $sessionChecker;

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
            $this->login();
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
        $this->onTransition(self::STATE_CONNECTED, self::STATE_STOPPING, function () {
            $this->stopping = true;
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

    protected function stopSessionChecker()
    {
        $this->loop->cancelTimer($this->sessionChecker);
        $this->sessionChecker = null;
    }

    protected function runSessionChecker()
    {
        $this->sessionChecker = $this->loop->addPeriodicTimer(150, function () {
            $this->getApi()->eventuallyLogin()->otherwise(function () {
                $this->loop->futureTick(function () {
                    $this->setState(self::STATE_FAILING);
                });
            });
        });
    }

    public function getApi()
    {
        return $this->api;
    }

    public function isReady()
    {
        return $this->getState() === self::STATE_CONNECTED;
    }

    public function getServerInfo()
    {
        return $this->serverInfo;
    }

    protected function scheduleNextAttempt($delay = 60)
    {
        if ($this->scheduledPollerStartup) {
            return;
        }
        $this->logger->info("Will try to reconnect in $delay seconds");
        $this->scheduledPollerStartup = $this->loop->addTimer($delay, function () {
            $this->setState(self::STATE_INIT);
        });
    }

    protected function eventuallyRemoveScheduledAttempt()
    {
        if ($this->scheduledPollerStartup) {
            $this->loop->cancelTimer($this->scheduledPollerStartup);
            $this->scheduledPollerStartup = null;
        }
    }

    protected function startWsdlDownload()
    {
        $this->wsdlPromise = RetryUnless::succeeding([$this, 'fetchWsdl'])
            ->setInterval(10)
            ->slowDownAfter(6, 60);

        $this->wsdlPromise->run($this->loop)
            ->then(function ($wsdlFile) {
                $this->wsdlFile = $wsdlFile;
                $this->wsdlPromise = null;
                $this->setState(self::STATE_LOGIN);
            }, function () {
                $this->wsdlPromise = null;
                if (! $this->stopping) {
                    $this->setState(self::STATE_FAILING);
                }
            });
    }

    protected function login()
    {
        $api = new VsphereApi($this->wsdlFile, $this->serverInfo, $this->curl, $this->loop, $this->logger);
        return $this->loginPromise = $api->eventuallyLogin()->then(function (UserSession $session) use ($api) {
            $this->api = $api;
            $this->loginPromise = null;
            $this->setState(self::STATE_CONNECTED);
        }, function (\Exception $e) {
            $this->loginPromise = null;
            if (! $this->stopping) {
                $this->setState(self::STATE_FAILING);
            }
            throw $e;
        });
    }

    public function stop()
    {
        $this->setState(self::STATE_STOPPING);
    }

    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->setState(self::STATE_INIT);
    }

    public function fetchWsdl()
    {
        $serverId = $this->serverInfo->get('id');
        $cacheDir = SafeCacheDir::getSubDirectory("wsdl-$serverId");
        $loader = new WsdlLoader($cacheDir, $this->logger, $this->serverInfo, $this->curl);
        return $loader->fetchInitialWsdlFile($this->loop);
    }
}
