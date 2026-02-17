<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use gipfl\Cli\Process;
use gipfl\Curl\CurlAsync;
use gipfl\LinuxHealth\Memory;
use gipfl\Log\Logger;
use gipfl\Log\PrefixLogger;
use gipfl\ReactUtils\RetryUnless;
use gipfl\SimpleDaemon\DaemonState;
use gipfl\SimpleDaemon\DaemonTask;
use gipfl\SimpleDaemon\SystemdAwareTask;
use gipfl\SystemD\NotifySystemD;
use Icinga\Application\Platform;
use Icinga\Data\ConfigObject;
use Icinga\Module\Vspheredb\Application\MemoryLimit;
use Icinga\Module\Vspheredb\Configuration;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceProcess;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\MappedClass\AboutInfo;
use Icinga\Module\Vspheredb\Polling\ApiConnection;
use Icinga\Module\Vspheredb\Polling\ApiConnectionHandler;
use Icinga\Module\Vspheredb\Polling\RestApi;
use Icinga\Module\Vspheredb\Polling\ServerInfo;
use Icinga\Module\Vspheredb\Polling\ServerSet;
use Icinga\Module\Vspheredb\Util;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Stream\Util as StreamUtil;
use RuntimeException;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Adapter_Exception;

use function React\Promise\resolve;

class VsphereDbDaemon implements DaemonTask, SystemdAwareTask, LoggerAwareInterface, EventEmitterInterface
{
    use EventEmitterTrait;
    use LoggerAwareTrait;

    public const PROCESS_NAME = 'Icinga::vSphereDB';

    public const COMPONENT_DB = 'db';
    public const COMPONENT_LOCALDB = 'localdb';
    public const COMPONENT_API = 'api';

    public const STATE_STOPPED  = 'stopped';
    public const STATE_STOPPING  = 'stopping';
    public const STATE_STARTING = 'starting';
    public const STATE_READY    = 'ready';
    public const STATE_FAILED   = 'failed';
    public const STATE_IDLE     = 'idle';

    /** @var ?LoopInterface */
    private ?LoopInterface $loop = null;

    /** @var ?array */
    protected ?array $dbConfig = null;

    /** @var ?Db */
    protected ?Db $connection = null;

    /** @var ?object */
    protected ?object $processInfo = null;

    /** @var int */
    protected int $delayOnFailed = 5;

    /** @var ?NotifySystemD */
    protected ?NotifySystemD $systemd = null;

    /** @var ?DbLogger */
    protected ?DbLogger $dbLogger = null;

    /** @var ?RemoteApi */
    protected ?RemoteApi $remoteApi = null;

    /** @var ?RemoteClient */
    protected ?RemoteClient $remoteClient = null;

    /** @var ?CurlAsync */
    protected ?CurlAsync $curl = null;

    /** @var ?ApiConnectionHandler */
    protected ?ApiConnectionHandler $apiConnectionHandler = null;

    /** @var ?DbProcessRunner */
    protected ?DbProcessRunner $dbRunner = null;

    /** @var ?DaemonState */
    protected ?DaemonState $daemonState = null;

    /** @var bool */
    protected bool $dbIsReady = false;

    /** @var array [splhash(ApiConnection) => [ Task, ... ]] */
    protected array $runningTasks = [];

    /** @var ?ConfigWatch */
    protected ?ConfigWatch $configWatch = null;

    /** @var array<string, string> */
    protected array $componentStates = [
        self::COMPONENT_DB      => self::STATE_STOPPED,
        self::COMPONENT_LOCALDB => self::STATE_STOPPED,
        self::COMPONENT_API     => self::STATE_STOPPED
    ];

    /**
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     */
    public function start(LoopInterface $loop): PromiseInterface
    {
        MemoryLimit::raiseTo('1024M');
        $this->loop = $loop;
        $logger = $this->logger;
        $this->daemonState = $this->initializeDaemonState();
        $this->setInitialDaemonState();
        $this->detectProcessInfo();
        $this->initializeDbLogger($logger); // TODO: move to sub process. Hint: needs no DB, has a queue
        $this->prepareApi($loop, $logger);
        $this->initializeDbProcess();
        $this->keepRefreshingServerConfig();
        $this->daemonState->setState(self::STATE_IDLE);

        return resolve(null);
    }

    /**
     * @return DaemonState
     */
    protected function initializeDaemonState(): DaemonState
    {
        $daemonState = new DaemonState();
        $daemonState->setComponentStates($this->componentStates);
        $daemonState->on(DaemonState::ON_CHANGE, function ($processTitle, $statusSummary) use ($daemonState) {
            $title = $processTitle;
            if (strlen($statusSummary !== 0)) {
                $title .= ": $statusSummary";
            }

            Process::setTitle($title);

            if ($this->systemd && strlen($statusSummary) > 0) {
                $this->systemd->setStatus($statusSummary);
            }
            foreach ($this->componentStates as $component => $state) {
                $newState = $daemonState->getComponentState($component);
                if ($state !== $newState) {
                    $this->loop->futureTick(function () use ($component, $state, $newState) {
                        $this->onComponentChange($component, $state, $newState);
                    });
                }
            }
            $this->componentStates = $daemonState->getComponentStates();
        });

        return $daemonState;
    }

    /**
     * @return void
     */
    protected function setInitialDaemonState(): void
    {
        $daemonState = $this->daemonState;
        $daemonState->setProcessTitle(self::PROCESS_NAME);
        $daemonState->setState(self::STATE_STARTING);
    }

    /**
     * @param string      $component
     * @param string      $formerState
     * @param string|null $currentState
     *
     * @return void
     */
    protected function onComponentChange(string $component, string $formerState, ?string $currentState): void
    {
        $this->logger->debug("[$component] component changed from $formerState to $currentState");
        if ($this->daemonState->getComponentState($component) !== $currentState) {
            $this->logger->warning(sprintf(
                "[%s] component should be %s, but is now %s. Race condition?",
                $component,
                $currentState,
                $this->daemonState->getComponentState($component)
            ));
        }
        if ($component === self::COMPONENT_DB) {
            if ($formerState === self::STATE_READY) {
                $this->stopConfigWatch();
                $this->stopComponent(self::COMPONENT_API);
                $this->stopComponent(self::COMPONENT_LOCALDB);
            } elseif ($currentState === self::STATE_IDLE) {
                $this->runConfigWatch();
            } elseif ($currentState === self::STATE_READY) {
                $this->setLocalDbState(self::STATE_STARTING);
            }
            if ($currentState === self::STATE_FAILED) {
                $this->loop->addTimer(10, function () {
                    $this->stopDbProcess();
                    $this->stopConfigWatch();
                    if ($this->daemonState->getComponentState(self::COMPONENT_DB) === self::STATE_FAILED) {
                        $this->initializeDbProcess();
                    }
                });
            }
        }
        if ($component === self::COMPONENT_LOCALDB) {
            if ($formerState === self::STATE_READY) {
                $this->stopComponent(self::COMPONENT_API);
            }
            switch ($currentState) {
                case self::STATE_STARTING:
                    $this->reconnectToDb();
                    break;
                case self::STATE_FAILED:
                    $this->logger->error('Failed. Will try to reconnect to the Database');
                    $this->eventuallyDisconnectFromDb();
                    $delay = $this->delayOnFailed;
                    $this->logger->warning("Failed. Reconnecting in {$delay}s");
                    $this->loop->addTimer($delay, function () {
                        if ($this->getLocalDbState() === self::STATE_FAILED) {
                            $this->setLocalDbState(self::STATE_STARTING);
                        }
                    });
                    break;
                case self::STATE_READY:
                    $this->setApiState(self::STATE_STARTING);
                    break;
                case self::STATE_STOPPING:
                    $this->eventuallyDisconnectFromDb();
                    $this->stopComponent(self::COMPONENT_API);
                    $this->setLocalDbState(self::STATE_STOPPED);
                    break;
            }
        }
        if ($component === self::COMPONENT_API) {
            switch ($currentState) {
                case self::STATE_STARTING:
                    $this->apiConnectionHandler->run($this->loop);
                    $this->setApiState(self::STATE_READY);
                    break;
                case self::STATE_READY:
                    $this->refreshConfiguredServers();
                    $this->daemonState->setState(self::STATE_READY);
                    break;
                case self::STATE_FAILED:
                    $this->logger->error('[api] failed');
                    // Intentional fall-through:
                    // no break
                case self::STATE_STOPPING:
                    if ($this->apiConnectionHandler) {
                        $this->apiConnectionHandler->stop();
                    }
                    $this->stopAllApiTasks();
                    $this->setApiState(self::STATE_STOPPED);
                    break;
            }
        }
    }

    /**
     * @param string $component
     *
     * @return void
     */
    protected function stopComponent(string $component): void
    {
        $state = $this->daemonState;
        if (! in_array($state->getComponentState($component), [self::STATE_STOPPED, self::STATE_STOPPING])) {
            $state->setComponentState($component, self::STATE_STOPPING);
            $this->daemonState->setState(self::STATE_IDLE);
        }
    }

    /**
     * @param string $state
     *
     * @return void
     */
    protected function setDbState(string $state): void
    {
        $this->daemonState->setComponentState(self::COMPONENT_DB, $state);
    }

    /**
     * @param string $state
     *
     * @return void
     */
    protected function setLocalDbState(string $state): void
    {
        $this->daemonState->setComponentState(self::COMPONENT_LOCALDB, $state);
    }

    /**
     * @param string $state
     *
     * @return void
     */
    protected function setApiState(string $state): void
    {
        $this->daemonState->setComponentState(self::COMPONENT_API, $state);
    }

    /**
     * @return string|null
     */
    protected function getApiState(): ?string
    {
        return $this->daemonState->getComponentState(self::COMPONENT_API);
    }

    /**
     * @return string|null
     */
    protected function getLocalDbState(): ?string
    {
        return $this->daemonState->getComponentState(self::COMPONENT_LOCALDB);
    }

    /**
     * @return void
     */
    protected function initializeDbProcess(): void
    {
        $dbRunner = new DbProcessRunner($this->logger);
        $this->setDbState(self::STATE_STARTING);
        $dbRunner->on('error', function (Exception $e) {
            $this->dbIsReady = false;
            $this->loop->futureTick(function () {
                $this->setDbState(self::STATE_FAILED);
            });
            $this->logger->error('DB runner is failing: ' . $e->getMessage());
        });
        $dbRunner->run($this->loop)->then(function () use ($dbRunner) {
            $this->dbRunner = $dbRunner;
            if ($this->remoteApi) {
                $this->remoteApi->setDbProcessRunner($dbRunner);
            }
            $this->loop->futureTick(function () {
                $this->setDbState(self::STATE_IDLE);
            });
        });
    }

    /**
     * @return void
     */
    protected function stopDbProcess(): void
    {
        if ($this->dbRunner) {
            $this->dbRunner->stop();
            $this->dbRunner = null;
            if ($this->remoteApi) {
                $this->remoteApi->setDbProcessRunner(null);
            }
        }
    }

    /**
     * @return void
     */
    protected function keepRefreshingServerConfig(): void
    {
        $refresh = function () {
            if ($this->daemonState->getComponentState(self::COMPONENT_LOCALDB) === self::STATE_READY) {
                $this->refreshConfiguredServers();
            }
        };
        $this->loop->addPeriodicTimer(10, $refresh);
        $this->loop->futureTick($refresh);
    }

    /**
     * @return PromiseInterface
     */
    public function stop(): PromiseInterface
    {
        try {
            $this->daemonState->setState(self::STATE_STOPPING);
            $this->logger->notice('Stopping vSphereDbDaemon');
            $this->dbRunner->stop();
            $this->eventuallyDisconnectFromDb();
        } catch (Exception $e) {
            $this->logger->error('Failed to stop vSphereDbDaemon: ' . $e->getMessage());
        }

        return resolve(null);
    }

    /**
     * @return void
     */
    protected function detectProcessInfo(): void
    {
        $this->processInfo = (object) [
            'instance_uuid' => Uuid::uuid4()->getBytes(),
            // 'ts_startup'      => Util::currentTimestamp(),
            'pid'           => posix_getpid(),
            'fqdn'          => Platform::getFqdn(),
            'username'      => Platform::getPhpUser(),
            'php_version'   => Platform::getPhpVersion()
        ];
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return void
     */
    protected function initializeDbLogger(LoggerInterface $logger): void
    {
        // TODO: ProcessInfo!
        $this->dbLogger = new DbLogger(
            $this->processInfo->instance_uuid,
            $this->processInfo->fqdn,
            $this->processInfo->pid
        );
        $this->dbLogger->on('error', function () {
            $this->setLocalDbState(self::STATE_FAILED);
        });
        $this->dbLogger->setLogger($logger);
        $logger->addWriter($this->dbLogger);
    }

    /**
     * @param ServerInfo    $server
     * @param AboutInfo     $about
     * @param UuidInterface $uuid
     *
     * @return void
     */
    protected function onNewConnectedServer(ServerInfo $server, AboutInfo $about, UuidInterface $uuid): void
    {
        if (VCenter::exists($uuid->getBytes(), $this->connection)) {
            $this->logger->info(sprintf('Attached %s to an existing vCenter', $server->get('host')));
            $vCenter = VCenter::load($uuid->getBytes(), $this->connection);
        } else {
            $this->logger->info(sprintf('Created a new vCenter object for %s', $server->get('host')));
            $vCenter = VCenter::create([], $this->connection);
            $vCenter->setMapped($about, $vCenter);
            $vCenter->set('name', $server->get('host'));
            $vCenter->set('instance_uuid', $uuid->getBytes());
            $vCenter->store();
        }
        $db = $this->connection->getDbAdapter();
        $db->update('vcenter_server', [
            'vcenter_id' => $vCenter->get('id')
        ], $db->quoteInto('id = ?', $server->getServerId()));
        $this->loop->futureTick(function () {
            $this->refreshConfiguredServers();
        });
    }

    /**
     * @param ApiConnection $connection
     *
     * @return void
     */
    protected function onApiConnection(ApiConnection $connection): void
    {
        $vCenter = VCenter::loadWithAutoIncId(
            $connection->getServerInfo()->getVCenterId(),
            $this->connection
        );
        $serverInfo =  $connection->getServerInfo();
        $logger = new PrefixLogger(sprintf(
            '[api %s (id=%d)] ',
            $serverInfo->get('host'),
            $serverInfo->get('id')
        ), $this->logger);
        $logger->info('connection is ready');

        try {
            $restApi = new RestApi($serverInfo, $vCenter, $this->curl, $logger);
            $this->launchTasksForConnection($connection, [
                new ObjectSync($vCenter, $connection->getApi(), $restApi, $this->dbRunner, $logger),
                new PerfDataSync($vCenter, $connection->getApi(), $this->curl, $this->loop, $logger)
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param ApiConnection $connection
     * @param DaemonTask[] $tasks
     *
     * @return void
     */
    protected function launchTasksForConnection(ApiConnection $connection, array $tasks): void
    {
        $idx = spl_object_hash($connection);
        $this->runningTasks[$idx] = $tasks;

        foreach ($tasks as $task) {
            $task->start($this->loop);
        }
    }

    /**
     * @param LoopInterface   $loop
     * @param LoggerInterface $logger
     *
     * @return void
     */
    protected function prepareApi(LoopInterface $loop, LoggerInterface $logger): void
    {
        $socketPath = Configuration::getSocketPath();

        $curl = new CurlAsync($loop);
        $this->curl = $curl;
        $this->apiConnectionHandler = $connection = new ApiConnectionHandler($curl, $logger);
        $this->remoteApi = new RemoteApi($connection, $curl, $loop, $logger);
        if ($this->dbRunner) {
            $this->remoteApi->setDbProcessRunner($this->dbRunner);
        }
        StreamUtil::forwardEvents($this->remoteApi, $this, [RpcNamespaceProcess::ON_RESTART]);
        $connection->on(
            ApiConnectionHandler::ON_INITIALIZED_SERVER,
            function (ServerInfo $server, AboutInfo $info, UuidInterface $uuid) {
                try {
                    $this->onNewConnectedServer($server, $info, $uuid);
                } catch (Exception $e) {
                    $this->logger->error('Failed to persist new Server: ' . $e->getMessage());
                }
            }
        );
        $connection->on(ApiConnectionHandler::ON_CONNECT, function (ApiConnection $connection) {
            try {
                $this->onApiConnection($connection);
            } catch (Exception $e) {
                $this->logger->error('Failed to deal with new API connection: ' . $e->getMessage());
            }
        });
        $connection->on(ApiConnectionHandler::ON_DISCONNECT, function (ApiConnection $connection) {
            $this->stopApiTasksForConnection($connection);
        });
        $this->remoteApi->run($socketPath, $loop);
        $this->remoteClient = new RemoteClient($socketPath, $loop);
    }

    /**
     * @param ApiConnection $connection
     *
     * @return void
     */
    protected function stopApiTasksForConnection(ApiConnection $connection): void
    {
        $this->stopApiTasksByConnectionIdx(spl_object_hash($connection));
    }

    /**
     * @param string $idx
     *
     * @return void
     */
    protected function stopApiTasksByConnectionIdx(string $idx): void
    {
        if (isset($this->runningTasks[$idx])) {
            /** @var DaemonTask $task */
            foreach ($this->runningTasks[$idx] as $task) {
                $task->stop();
            }
            unset($this->runningTasks[$idx]);
        }
    }

    /**
     * @return void
     */
    protected function stopAllApiTasks(): void
    {
        foreach ($this->runningTasks as $tasks) {
            foreach ($tasks as $task) {
                $task->stop();
            }
        }

        $this->runningTasks = [];
    }

    /**
     * @return void
     */
    protected function onConnected(): void
    {
        $fail = function (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->setLocalDbState(self::STATE_FAILED);
        };
        $refresh = function () {
            $this->refreshMyState();
        };
        try {
            if ($this->hasSchema()) {
                $this->logger->notice('[localdb] ready');
                $this->setLocalDbState(self::STATE_READY);
            } else {
                $fail(new Exception('DB has no schema'));
            }
        } catch (Exception $e) {
            $fail($e);
        }

        RetryUnless::failing($refresh)
            ->setInterval(5)
            ->run($this->loop)
            ->then($fail);
    }

    /**
     * @return bool
     */
    protected function hasSchema(): bool
    {
        return (Db::migrationsForDb($this->connection))->hasSchema();
    }

    /**
     * @return PromiseInterface
     */
    protected function sendDbConfigToRunner(): PromiseInterface
    {
        $this->logger->notice('[db] sending DB config to child process');
        if (! $this->daemonState->getComponentState(self::COMPONENT_DB) === self::STATE_READY) {
            $this->logger->warning('[db] DB runner is NOT ready, not sending config');

            return resolve(null);
        }
        if ($this->dbConfig === null) {
            return $this->dbRunner->request('db.clearDbConfig')->then(function () {
                $this->setDbState(self::STATE_STOPPED);
            }, function (Exception $e) {
                $this->logger->error('[db] clearing DB config failed: ' . $e->getMessage());
                $this->setDbState(self::STATE_FAILED);
            });
        }

        return $this->dbRunner->request('db.setDbConfig', ['config' => $this->dbConfig]);
    }

    /**
     * @return void
     */
    protected function reconnectToDb(): void
    {
        if ($this->connection !== null) {
            $this->eventuallyDisconnectFromDb();
            $this->logger->notice('[localdb] reconnecting');
        }
        $this->setLocalDbState(self::STATE_STARTING);
        RetryUnless::succeeding(function () {
            if ($this->dbConfig === null) {
                throw new RuntimeException('Got no valid DB configuration');
            }

            return $this->connectToDb($this->dbConfig);
        })->slowDownAfter(5, 15)->run($this->loop)->then(function (Db $connection) {
            $this->connection = $connection;
            $this->onConnected();
        });
    }

    /**
     * @param $config
     *
     * @return Db
     */
    protected function connectToDb($config): Db
    {
        $connection = new Db(new ConfigObject($config));
        $connection->getDbAdapter()->getConnection();
        $this->dbLogger->setDb($connection);
        $this->loop->futureTick(function () {
            $this->refreshConfiguredServers();
        });

        return $connection;
    }

    /**
     * @param bool $refresh
     *
     * @return void
     */
    protected function eventuallyDisconnectFromDb(bool $refresh = true): void
    {
        if ($this->connection !== null) {
            try {
                if ($refresh) {
                    $this->refreshMyState(false);
                }
                $this->connection->getDbAdapter()->closeConnection();

                if ($this->daemonState->getState() === self::STATE_STOPPING) {
                    $this->logger->notice('[localdb] database connection has been closed');
                } else {
                    $this->logger->error('[localdb] database connection has been closed');
                }
            } catch (Exception $e) {
                $this->logger->error(
                    '[localdb] ignored an error while closing the DB connection: '
                    . $e->getMessage()
                );
            }
            $this->connection = null;
            $this->dbLogger->setDb(null);
        }
    }

    /**
     * @return void
     */
    protected function runConfigWatch(): void
    {
        if ($this->configWatch) {
            return;
        }
        $config = new ConfigWatch();
        $config->on(ConfigWatch::ON_CONFIG, function ($config) {
            $this->onDbConfig($config);
        });
        $this->configWatch = $config;
        $config->run($this->loop);
    }

    /**
     * @param array|null $config
     *
     * @return void
     */
    protected function onDbConfig(?array $config): void
    {
        if ($config === null) {
            $this->setDbState('config error');
            if ($this->dbConfig === null) {
                $this->logger->error('[configwatch] Got no valid DB configuration');

                return;
            }
            $this->logger->error('[configwatch] There is no longer a valid DB configuration');
        } else {
            $this->logger->notice('[configwatch] DB configuration loaded');
        }
        $this->dbConfig = $config;
        $sent = $this->sendDbConfigToRunner();
        $sent->then(function () {
            $this->stopComponent(self::COMPONENT_API);
            $this->setDbState(self::STATE_READY);
        }, function (Exception $e) {
            $this->logger->error('[configwatch] Sending DB Config failed: ' . $e->getMessage());
            $this->setDbState(self::STATE_FAILED);
        });
    }

    /**
     * @return void
     */
    protected function stopConfigWatch(): void
    {
        if ($this->configWatch) {
            $this->configWatch->stop();
            $this->configWatch = null;
        }
    }

    /**
     * @param bool $disconnectOnError
     *
     * @return void
     */
    protected function refreshMyState(bool $disconnectOnError = true): void
    {
        if ($this->connection === null) {
            return;
        }
        try {
            $db = $this->connection->getDbAdapter();
            $updated = $db->update('vspheredb_daemon', [
                'ts_last_refresh' => Util::currentTimestamp(),
                'process_info' => json_encode($this->getProcessInfo())
            ], $db->quoteInto('instance_uuid = ?', DbUtil::quoteBinaryCompat($this->processInfo->instance_uuid, $db)));

            if (! $updated) {
                $this->insertMyState($db);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            if ($disconnectOnError) {
                $this->eventuallyDisconnectFromDb(false);
            }
        }
    }

    /**
     * @param Zend_Db_Adapter_Abstract $db
     *
     * @return void
     *
     * @throws Zend_Db_Adapter_Exception
     */
    protected function insertMyState(Zend_Db_Adapter_Abstract $db): void
    {
        $db->insert('vspheredb_daemon', [
            'instance_uuid' => $this->processInfo->instance_uuid,
            'ts_last_refresh' => Util::currentTimestamp(),
            'process_info'    => json_encode($this->getProcessInfo())
        ] + (array) $this->processInfo);
    }

    /**
     * @return object
     */
    protected function getProcessInfo(): object
    {
        global $argv;
        /** @var int $pid */
        $pid = $this->processInfo->pid;

        return (object) [$pid => (object) [
            'command' => implode(' ', $argv),
            'running' => true,
            'memory'  => Memory::getUsageForPid($pid)
        ]];
    }

    /**
     * @return void
     */
    protected function refreshConfiguredServers(): void
    {
        if ($this->connection === null) {
            return;
        }
        try {
            if ($this->daemonState->getComponentState(self::COMPONENT_API) === self::STATE_READY) {
                $this->apiConnectionHandler->setServerSet(
                    ServerSet::fromServers(VCenterServer::loadEnabledServers($this->connection))
                );
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to refresh server list: ' . $e->getMessage());
            $this->setLocalDbState(self::STATE_FAILED);
        }
    }

    /**
     * @param NotifySystemD $systemd
     *
     * @return void
     */
    public function setSystemd(NotifySystemD $systemd): void
    {
        $this->systemd = $systemd;
    }
}
