<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\SystemD\NotifySystemD;
use Icinga\Application\Logger;
use Icinga\Application\Platform;
use Icinga\Data\ConfigObject;
use Icinga\Module\Vspheredb\CliUtil;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\Migrations;
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

    protected $lastCliTitle;

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
        CliUtil::setTitle('Icinga::vSphereDB::main');
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
            $this->refreshCliTitle();
        };
        $loop->addPeriodicTimer(3, $refresh);
        $loop->futureTick($refresh);

        if ($ownLoop) {
            $loop->run();
        }
    }

    protected function refreshCliTitle()
    {
        $title = sprintf(
            'Icinga::vSphereDB::main: %d active runner%s',
            count($this->running),
            count($this->running) === 1 ? '' : 's'
        );

        if ($title !== $this->lastCliTitle) {
            CliUtil::setTitle($title);
            $this->lastCliTitle = $title;
        }
    }

    protected function onConnected()
    {
        $this->setDaemonStatus('Connected to the database', 'info');

        $fail = function (Exception $e) {
            Logger::error($e->getMessage());
            $this->setState('failed');
        };
        $refresh = function () {
            $this->refreshMyState();
        };
        try {
            if ($this->hasSchema()) {
                $this->runDbCleanup();
            } else {
                $this->setDaemonStatus('DB has no schema', 'error');
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

    protected function hasSchema()
    {
        return (new Migrations($this->connection))->hasSchema();
    }

    protected function runDbCleanup()
    {
        $this->setDaemonStatus('Running DB cleanup (this could take some time)', 'info');
        $db = $this->connection->getDbAdapter();
        // Delete all damon entries older than the two most recently running daemons
        $where = <<<WHERE
        ts_last_refresh < (
          SELECT ts_last_refresh FROM (
            SELECT ts_last_refresh
              FROM vspheredb_daemon
             ORDER BY ts_last_refresh DESC
              LIMIT 2
          ) toptwo ORDER BY ts_last_refresh ASC LIMIT 1
        )
WHERE;
        $result = $db->delete('vspheredb_daemon', $where);
        if ($result > 0) {
            $this->setDaemonStatus(
                "Removed information related to $result formerly running daemon instance(s)",
                'info'
            );
        }
        $db->query('OPTIMIZE TABLE vspheredb_daemon')->execute();
        $where = <<<QUERY
        NOT EXISTS (
          SELECT 1 FROM vspheredb_daemon d
           WHERE d.instance_uuid = vspheredb_daemonlog.instance_uuid
        )
QUERY;
        $result = $db->delete('vspheredb_daemonlog', $where);
        if ($result > 0) {
            $this->setDaemonStatus(
                "Removed $result outdated daemon log lines, optimizing table",
                'info'
            );
        }
        $db->query('OPTIMIZE TABLE vspheredb_daemonlog')->execute();
        $this->setDaemonStatus('DB has been cleaned up', 'info');
    }

    protected function setDaemonStatus($status, $logLevel = null, $sendReady = false)
    {
        if ($logLevel !== null) {
            Logger::$logLevel($status);
        }
        if ($this->systemd) {
            if ($sendReady) {
                $this->systemd->setReady($status);
            } else {
                $this->systemd->setStatus($status);
            }
        }
    }

    protected function onDisconnected()
    {
        $this->setDaemonStatus('Database connection has been closed', 'error');
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
        })->onTransition('connected', 'disconnected', function () {
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
            $this->setDaemonStatus('Failed. Will try to reconnect to the Database');
            $this->eventuallyDisconnectFromDb();
        })->onTransition(['started', 'connected', 'disconnected'], 'failed', function () {
            $this->onFailed();
        })->onTransition('failed', 'disconnected', function () {

        });

        // External events:
        $this->runConfigWatch();
        $this->setDaemonStatus('Ready to run', 'info', true);
        $this->initializeStateMachine('started');
    }

    protected function stopRunners()
    {
        // There is some redundancy here to really make sure they're gone
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
        $this->setDaemonStatus('Reconnecting to DB', 'info');
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
                if (! in_array($this->getState(), ['disconnected', 'shutdown'])) {
                    $this->setState('disconnected');
                }
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
                    $this->setDaemonStatus('Got no valid DB configuration', 'error');
                } else {
                    $this->setDaemonStatus('There is no longer a valid DB configuration', 'error');
                    $this->dbConfig = $config;
                    $this->stopRunners();
                    $this->reconnectToDb();
                }
            } else {
                $this->setDaemonStatus('DB configuration loaded', 'info');
                $this->dbConfig = $config;
                $this->stopRunners();
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
            $this->setDaemonStatus('Shutting down', 'info');
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
        $this->refreshCliTitle();
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
        $runner->run($this->loop)->otherwise(function () use ($vCenterId, $serverId) {
            $this->pauseFailedRunner($vCenterId, $serverId);
        });
        $runner->on('processStopped', function ($pid) use ($vCenterId, $serverId) {
            Logger::debug("Pid $pid stopped");
            $this->refreshMyState();
            try {
                $this->refreshMyState();
            } catch (Exception $e) {
                Logger::error($e->getMessage());
                $this->eventuallyDisconnectFromDb();
            }
        });
        $runner->on('failed', function ($pid) use ($vCenterId, $serverId) {
            $this->pauseFailedRunner($vCenterId, $serverId, $pid);
        });

        return $runner;
    }

    protected function pauseFailedRunner($vCenterId, $serverId, $pid = null)
    {
        if (! isset($this->running[$serverId])) {
            Logger::error("Server for vCenterID=$vCenterId failed, there is no related runner");
            return;
        }
        if ($pid === null) {
            $pidInfo = '';
        } else {
            $pidInfo = " (PID $pid)";
        }
        Logger::error("Server for vCenterID=$vCenterId failed$pidInfo, will try again in 30 seconds");
        $this->running[$serverId]->stop();
        unset($this->running[$vCenterId]);
        gc_collect_cycles();
        $this->blackListed[$serverId] = true;
        $this->loop->addTimer(30, function () use ($serverId) {
            unset($this->blackListed[$serverId]);
        });
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
            $this->setDaemonStatus('Failed to refresh server list');
            Logger::error($e->getMessage());
            $this->setState('failed');
        }
    }
}
