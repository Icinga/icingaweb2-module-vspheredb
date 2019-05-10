<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use Evenement\EventEmitterTrait;
use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\LinuxUtils;
use Icinga\Module\Vspheredb\Rpc\LogProxy;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

/**
 * Class ServerRunner
 * @package Icinga\Module\Vspheredb\Daemon
 */
class ServerRunner
{
    use EventEmitterTrait;

    /** @var IcingaCliRunner */
    protected $icingaCli;

    /** @var VCenterServer */
    protected $server;

    /** @var LoopInterface */
    protected $loop;

    /** @var Process[] */
    protected $running = [];

    /** @var LogProxy */
    protected $logProxy;

    protected $health;

    protected $timer;

    public function __construct(VCenterServer $server)
    {
        Logger::info('Constructing ServerRunner');
        $this->health = (object) [];
        $this->server = $server;
    }

    public function forwardLog(LogProxy $logProxy)
    {
        $this->logProxy = $logProxy;

        return $this;
    }

    /**
     * @param LoopInterface $loop
     * @return \React\Promise\Promise
     */
    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $health = function () {
            $this->checkRunningProcessHealth();
        };
        $this->timer = $loop->addPeriodicTimer(1, $health);

        return RetryUnless::succeeding(function () {
            return $this->initializeServer();
        })->run($loop)->then(function ($vCenter) {
            $this->sync($vCenter);
        });
    }

    public function stop()
    {
        if ($this->timer !== null) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
        $this->stopRunningServers();
        $this->server = null;
        $this->icingaCli = null;
        $this->logProxy = null;
        $this->loop = null;
    }

    protected function initializeServer()
    {
        $server = $this->server;
        $hostName = $server->get('host');
        Logger::info('Initializing vCenter for %s', $hostName);

        $command = $this->prepareCliCommand([
            'vspheredb',
            'task',
            'initialize',
            '--rpc',
            '--debug',
            '--serverId',
            $server->get('id')
        ]);
        $command->on('start', function (Process $process) {
            $this->onProcessStarted($process);
        });

        return $command->run($this->loop)->then(function () use ($server) {
            $connection = $server->getConnection();
            $server = VCenterServer::load($server->get('id'), $connection);
            $db = $connection->getDbAdapter();
            $uuid = $db->fetchOne(
                $db->select()
                    ->from('vcenter', 'instance_uuid')
                    ->where('id = ?', $server->get('vcenter_id'))
            );

            return VCenter::load($uuid, $connection);
        });
    }

    protected function sync(VCenter $vCenter)
    {
        $id = $vCenter->get('id');
        // TODO: log Host?
        Logger::info('Running vCenter Sync for ID=%s', $id);
        $command = $this->prepareCliCommand([
            'vspheredb',
            'task',
            'sync',
            '--rpc',
            '--debug',
            '--vCenterId',
            $id
        ]);
        $command->on('start', function (Process $process) {
            $this->onProcessStarted($process, true);
        });

        return $command->run($this->loop);
    }

    protected function onProcessStarted(Process $process, $mustRun = false)
    {
        $pid = $process->getPid();
        $this->running[$pid] = $process;
        $this->checkRunningProcessHealth();
        $this->emit('processStarted', [$pid]);
        $process->on('exit', function () use ($pid, $mustRun) {
            unset($this->running[$pid]);
            $this->checkRunningProcessHealth();
            $this->emit('processStopped', [$pid]);
            if ($mustRun) {
                $this->emit('failed', [$pid]);
            }
        });
    }

    public function getProcessInfo()
    {
        return $this->health;
    }

    protected function checkRunningProcessHealth()
    {
        $info = [];

        foreach ($this->running as $pid => $process) {
            $info[$pid] = (object) [
                'command' => preg_replace('/^exec /', '', $process->getCommand()),
                'running' => $process->isRunning(),
                'memory'  => LinuxUtils::getMemoryUsageForPid($pid)
            ];
        }

        $this->health = $info;
    }

    protected function prepareCliCommand($arguments = null)
    {
        if ($this->icingaCli === null) {
            $this->icingaCli = IcingaCliRunner::forArgv();
        }

        $command = new IcingaCliRpc($this->icingaCli);

        $command->on('error', function (Exception $e) {
            Logger::error(rtrim($e->getMessage()));
            $this->stop();
        });
        if ($arguments) {
            $command->setArguments($arguments);
        }
        if ($this->logProxy) {
            $command->rpc()->setHandler($this->logProxy, 'logger');
        }

        return $command;
    }

    protected function stopRunningServers()
    {
        foreach ($this->running as $pid => $process) {
            $process->terminate(SIGTERM);
            $this->loop->addTimer(5, function () use ($process, $pid) {
                if ($process->isRunning()) {
                    Logger::error("Process $pid is still running, sending SIGKILL");
                    $process->terminate(SIGKILL);
                }
            });
        }
    }

    public function __destruct()
    {
        $this->stop();
    }
}
