<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Exception;
use gipfl\Protocol\JsonRpc\Connection;
use gipfl\Protocol\NetString\StreamWrapper;
use Icinga\Module\Vspheredb\CliUtil;
use Icinga\Module\Vspheredb\Daemon\SyncRunner;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\Rpc\JsonRpcLogWriter;
use Icinga\Module\Vspheredb\Rpc\Logger;
use React\EventLoop\Factory as Loop;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

/**
 * Sync a vCenter or ESXi host
 */
class TaskCommand extends CommandBase
{
    /** @var LoopInterface */
    protected $loop;

    public function init()
    {
        parent::init();
        $this->loop = Loop::create();
        if ($this->params->get('rpc')) {
            $this->enableRpc();
        }
    }

    /**
     * Connect to a vCenter, create/update it's base definition
     *
     * USAGE
     *
     * icingacli vsphere task initialize --serverId <id> [--rpc]
     */
    public function initializeAction()
    {
        $this->loop->futureTick(function () {
            $hostname = null;
            try {
                CliUtil::setTitle('Icinga::vSphereDB::initialize');
                $vCenter = $this->requireVCenterServer();
                $hostname = $vCenter->get('host');
                CliUtil::setTitle(sprintf('Icinga::vSphereDB::initialize (%s)', $hostname));
                $vCenter->initialize();
                $this->loop->stop();
            } catch (Exception $e) {
                $this->failFriendly('initialize', $e, $hostname);
            }
        });
        $this->loop->run();
    }

    /**
     * Sync all objects
     *
     * Still a prototype
     *
     * USAGE
     *
     * icingacli vsphere task sync --vCenterId <id> [--rpc]
     */
    public function syncAction()
    {
        $this->loop->futureTick(function () {
            $hostname = null;
            try {
                CliUtil::setTitle('Icinga::vSphereDB::sync');
                $vCenter = $this->requireVCenter();
                $hostname = $vCenter->getFirstServer()->get('host');
                CliUtil::setTitle(sprintf('Icinga::vSphereDB::sync (%s)', $hostname));
                $time = microtime(true);
                (new SyncRunner($vCenter))
                    ->showTrace($this->showTrace())
                    ->on('beginTask', function ($taskName) use ($hostname, & $time) {
                        CliUtil::setTitle(sprintf('Icinga::vSphereDB::sync (%s: %s)', $hostname, $taskName));
                        $time = microtime(true);
                    })
                    ->on('endTask', function ($taskName) use ($hostname, & $time) {
                        CliUtil::setTitle(sprintf('Icinga::vSphereDB::sync (%s)', $hostname));
                        $duration = microtime(true) - $time;
                        Logger::debug(sprintf(
                            'Task "%s" took %.2Fms on %s',
                            $taskName,
                            ($duration * 1000),
                            $hostname
                        ));
                    })
                    ->run($this->loop)
                    ->then(function () use ($hostname) {
                        $this->failFriendly('sync', 'Sync stopped. Should not happen', $hostname);
                    })->otherwise(function ($reason = null) use ($hostname) {
                        $this->failFriendly('sync', $reason, $hostname);
                    });
            } catch (Exception $e) {
                $this->failFriendly('sync', $e, $hostname);
            }
        });
        $this->loop->run();
    }

    protected function requireVCenter()
    {
        return VCenter::loadWithAutoIncId(
            $this->requiredParam('vCenterId'),
            Db::newConfiguredInstance()
        );
    }

    protected function requireVCenterServer()
    {
        return VCenterServer::loadWithAutoIncId(
            $this->requiredParam('serverId'),
            Db::newConfiguredInstance()
        );
    }

    protected function enableRpc()
    {
        // stream_set_blocking(STDIN, 0);
        // stream_set_blocking(STDOUT, 0);
        // print_r(stream_get_meta_data(STDIN));
        // stream_set_write_buffer(STDOUT, 0);
        // ini_set('implicit_flush', 1);
        $netString = new StreamWrapper(
            new ReadableResourceStream(STDIN, $this->loop),
            new WritableResourceStream(STDOUT, $this->loop)
        );
        $jsonRpc = new Connection();
        $jsonRpc->handle($netString);

        Logger::replaceRunningInstance(new JsonRpcLogWriter($jsonRpc));
    }

    protected function shorten($message, $length)
    {
        if (strlen($message) > $length) {
            return substr($message, 0, $length - 2) . '...';
        } else {
            return $message;
        }
    }

    public function failFriendly($task, $error = 'unknown error', $subject = null)
    {
        // Just in case the loop will hang, show our error state
        if ($error instanceof Exception) {
            $message = $error->getMessage();
        } else {
            $message = $error;
        }

        CliUtil::setTitle(sprintf(
            'Icinga::vSphereDB::%s: (%sfailed: %s)',
            $task,
            $subject ? "$subject: " : '',
            $this->shorten($error->getMessage(), 60)
        ));
        Logger::error($message);
        $this->loop->addTimer(0.1, function () {
            $this->loop->stop();
            exit(1);
        });
    }
}
