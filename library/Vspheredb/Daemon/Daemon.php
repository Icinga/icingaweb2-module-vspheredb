<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\SystemD\NotifySystemD;
use Icinga\Application\Logger;
use Icinga\Application\Platform;
use Icinga\Data\ConfigObject;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\LinuxUtils;
use Icinga\Module\Vspheredb\Rpc\LogProxy;
use Icinga\Module\Vspheredb\Util;
use React\EventLoop\Factory as Loop;
use React\EventLoop\LoopInterface;
use RuntimeException;

class Daemon
{
    use StateMachine;

    /** @var LoopInterface */
    private $loop;

    /** @var array|null */
    protected $dbConfig;

    /** @var Db */
    protected $connection;

    /** @var object */
    protected $processInfo;

    /** @var bool */
    protected $shuttingDown = false;

    /** @var ServerRunner[] */
    protected $running = [];

    /** @var array [VCenterServer->get('id') => true] */
    protected $blackListed = [];

    protected $delayOnFailed = 5;

    /** @var NotifySystemD|boolean */
    protected $systemd;

    public function __construct()
    {
        $this->detectProcessInfo();
    }

    protected function detectProcessInfo()
    {
        $this->processInfo = (object) [
            'instance_uuid' => Util::generateUuid(),
            'pid'           => posix_getpid(),
            'fqdn'          => Platform::getFqdn(),
            'username'      => Platform::getPhpUser(),
            'php_version'   => Platform::getPhpVersion(),
        ];
    }

    public function run(LoopInterface $loop = null)
    {
        $ownLoop = $loop === null;
        if ($ownLoop) {
            $loop = Loop::create();
        }

        $this->loop = $loop;
        $this->registerSignalHandlers();
        $this->systemd = NotifySystemD::ifRequired($loop);
        $this->makeReady();
        $refresh = function () {
            $this->refreshConfiguredServers();
        };
        $loop->addPeriodicTimer(3, $refresh);
        $loop->futureTick($refresh);

        if ($ownLoop) {
            $loop->run();
        }
    }

    protected function onConnected()
    {
        Logger::info('Database connection has been established');
        if ($this->systemd) {
            $this->systemd->setReady('Database connection has been established');
        }
        $reconnect = function (Exception $e) {
            Logger::error($e->getMessage());
            $this->setState('failed');
        };
        $refresh = function () {
            $this->refreshMyState();
        };
        RetryUnless::failing($refresh)
            ->setInterval(5)
            ->run($this->loop)
            ->then($reconnect);
    }

    protected function setDaemonStatus($status)
    {
        Logger::info($status);
        if ($this->systemd) {
            $this->systemd->setStatus($status);
        }
    }

    protected function onDisconnected()
    {
        $this->setDaemonStatus('Database connection has been closed');
        // state cannot be shutdown
        if ($this->getState() !== 'shutdown') {
            $this->reconnectToDb();
        }
    }

    protected function makeReady()
    {
        // Hint: currently disconnected isn't triggered.
        $this->onTransition(['started', 'failed', 'disconnected'], 'connected', function () {
            $this->onConnected();
        })->onState('disconnected', function () {
            $this->onDisconnected();
        })->onTransition([
            'started',
            'connected',
            'disconnected',
            'failed',
        ], 'shutdown', function () {
            $this->shutdown();
        })->onTransition(['connected', 'started'], 'failed', function () {
            Logger::error('Failed. Will try to reconnect to the Database');
            if ($this->systemd) {
                $this->systemd->setStatus('Failed. Will try to reconnect to the Database');
            }
            $this->eventuallyDisconnectFromDb();
        })->onTransition(['started', 'connected', 'disconnected'], 'failed', function () {
            $this->onFailed();
        });

        // External events:
        $this->runConfigWatch();
        $this->initializeStateMachine('started');
    }

    protected function stopRunners()
    {
        if (! empty($this->running)) {
            Logger::info('Stopping remaining child processes');
        }
        foreach ($this->running as $serverRunner) {
            $serverRunner->stop();
        }

        foreach (array_keys($this->running) as $id) {
            unset($this->running[$id]);
        }
        $this->running = [];
        gc_collect_cycles();
    }

    protected function onFailed()
    {
        $delay = $this->delayOnFailed;
        Logger::warning("Failed. Reconnecting in ${delay}s");
        $this->loop->addTimer($delay, function () {
            $this->reconnectToDb();
        });
        $this->stopRunners();
    }

    protected function reconnectToDb()
    {
        if ($this->systemd) {
            $this->systemd->setReloading('Reconnecting to DB');
        }
        $this->eventuallyDisconnectFromDb();
        RetryUnless::succeeding(function () {
            if ($this->dbConfig === null) {
                throw new RuntimeException('Got no valid DB configuration');
            }

            return $this->connectToDb($this->dbConfig);
        })->slowDownAfter(5, 15)->run($this->loop)->then(function (Db $connection) {
            $this->connection = $connection;
            $this->setState('connected');
        });
    }

    protected function connectToDb($config)
    {
        $connection = new Db(new ConfigObject($config));
        $connection->getDbAdapter()->getConnection();

        return $connection;
    }

    protected function eventuallyDisconnectFromDb()
    {
        if ($this->connection !== null) {
            try {
                $this->connection->getDbAdapter()->closeConnection();
            } catch (Exception $e) {
                Logger::error(
                    'Ignored an error while closing the DB connection: '
                    . $e->getMessage()
                );
            }
            $this->connection = null;
        }
    }

    protected function runConfigWatch()
    {
        $config = new ConfigWatch();
        $config->on('dbConfig', function ($config) {
            if ($config === null) {
                if ($this->dbConfig === null) {
                    Logger::error('Got no valid DB configuration');
                } else {
                    Logger::error('There is no longer a valid DB configuration');
                    $this->dbConfig = $config;
                    $this->reconnectToDb();
                }
            } else {
                $this->setDaemonStatus('DB configuration loaded');
                $this->dbConfig = $config;
                $this->reconnectToDb();
            }
        });
        $config->run($this->loop);
    }

    protected function registerSignalHandlers()
    {
        $func = function ($signal) use (&$func) {
            $this->shutdownWithSignal($signal, $func);
        };
        $this->loop->addSignal(SIGINT, $func);
        $this->loop->addSignal(SIGTERM, $func);
    }

    protected function shutdownWithSignal($signal, &$func)
    {
        $this->loop->removeSignal($signal, $func);
        $this->setState('shutdown');
    }

    protected function shutdown()
    {
        try {
            $this->setDaemonStatus('Shutting down');
            $this->eventuallyDisconnectFromDb();
        } catch (Exception $e) {
            if ($this->systemd) {
                $this->systemd->setError(sprintf(
                    'Failed to safely shutdown, stopping anyways: %s',
                    $e->getMessage()
                ));
            }
            Logger::error(
                'Failed to safely shutdown, stopping anyways: %s',
                $e->getMessage()
            );
        }
        $this->loop->stop();
    }

    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function refreshMyState()
    {
        if ($this->connection === null) {
            return;
        }
        $db = $this->connection->getDbAdapter();
        $updated = $db->update('vspheredb_daemon', [
            'instance_uuid' => $this->processInfo->instance_uuid,
            'ts_last_refresh' => Util::currentTimestamp(),
            'process_info'    => json_encode($this->getProcessInfo()),
        ], $db->quoteInto('instance_uuid = ?', $this->processInfo->instance_uuid));

        if (! $updated) {
            $this->insertMyState($db);
        }
    }

    /**
     * @param \Zend_Db_Adapter_Abstract $db
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function insertMyState(\Zend_Db_Adapter_Abstract $db)
    {
        $db->insert('vspheredb_daemon', [
            'instance_uuid' => $this->processInfo->instance_uuid,
            'ts_last_refresh' => Util::currentTimestamp(),
            'process_info'    => json_encode($this->getProcessInfo()),
        ] + (array) $this->processInfo);
    }

    protected function truncateDaemonLog(\Zend_Db_Adapter_Abstract $db)
    {
        // TODO.
    }

    protected function getProcessInfo()
    {
        global $argv;
        $pid = $this->processInfo->pid;
        $info = (object) [$pid => (object) [
            'command' => implode(' ', $argv),
            'running' => true,
            'memory'  => LinuxUtils::getMemoryUsageForPid($pid)
        ]];

        foreach ($this->running as $runner) {
            foreach ($runner->getProcessInfo() as $pid => $details) {
                $info->$pid = $details;
            }
        }

        return $info;
    }

    /**
     * @param VCenter[] $vCenters
     * @param VCenterServer[] $vServers
     */
    protected function checkRequiredProcesses($vCenters, $vServers)
    {
        $required = [];
        $candidates = [];
        foreach ($vServers as $id => $server) {
            $id = (int) $id;
            if (isset($this->blackListed[$id])) {
                continue;
            }
            $vCenterId = $server->get('vcenter_id');
            if ($vCenterId === null) {
                $required[$id] = $server;
            } else {
                $candidates[$vCenterId][$id] = $server;
            }
        }

        foreach ($vCenters as $id => $vCenter) {
            if (isset($candidates[$id])) {
                // Pick the first one. TODO: mark failing ones, try others
                foreach ($candidates[$id] as $serverId => $server) {
                    $required[$serverId] = $server;
                    break;
                }
            } else {
                // Logger::info(sprintf('vCenter ID=%s has no Server', $id));
            }
        }

        foreach ($required as $id => $vServer) {
            if (! isset($this->running[$id])) {
                Logger::info("vCenter ID=$id is now starting");
                $this->running[$id] = $this->runServer($vServer);
            }
        }

        foreach ($this->running as $id => $serverRunner) {
            if (! isset($required[$id])) {
                $this->running[$id]->stop();
                unset($this->running[$id]);
            }
        }
        gc_collect_cycles();
    }

    /**
     * @param VCenterServer $server
     * @return ServerRunner
     */
    protected function runServer(VCenterServer $server)
    {
        $runner = new ServerRunner($server);
        $vCenterId = $server->get('vcenter_id');
        $serverId = $server->get('id');
        Logger::info("Starting for vCenterID=$vCenterId");
        /** @var Db $connection */
        $connection = $server->getConnection();
        $logProxy = new LogProxy($connection, $this->processInfo->instance_uuid);
        $runner->forwardLog($logProxy);
        $runner->run($this->loop);
        $runner->on('processStopped', function ($pid) use ($vCenterId) {
            // Logger::info("Pid $pid stopped");
            try {
                $this->refreshMyState();
            } catch (Exception $e) {
                $this->eventuallyDisconnectFromDb();
            }
        });
        $runner->on('failed', function ($pid) use ($vCenterId, $serverId) {
            if (isset($this->running[$vCenterId])) {
                Logger::error("Server for vCenterID=$vCenterId failed (PID $pid), will try again in 30 seconds");
                $this->running[$vCenterId]->stop();
                unset($this->running[$vCenterId]);
                gc_collect_cycles();
                $this->blackListed[$serverId] = true;
                $this->loop->addTimer(30, function () use ($serverId) {
                    unset($this->blackListed[$serverId]);
                });
            }
        });

        return $runner;
    }

    protected function refreshConfiguredServers()
    {
        try {
            if ($this->getState() === 'connected') {
                $vCenters = VCenter::loadAll($this->connection, null, 'id');
                $vServers = VCenterServer::loadAll($this->connection, null, 'id');
                $this->checkRequiredProcesses($vCenters, $vServers);
            } else {
                $this->stopRunners();
            }
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->setState('failed');
        } catch (\Error $e) {
            Logger::error($e->getMessage());
            $this->setState('failed');
        }
    }
}
