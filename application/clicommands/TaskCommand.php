<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use gipfl\Protocol\JsonRpc\Connection;
use gipfl\Protocol\NetString\StreamWrapper;
use Icinga\Module\Vspheredb\Daemon\SyncRunner;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\Rpc\JsonRpcLogWriter;
use Icinga\Module\Vspheredb\Rpc\Logger;
use React\EventLoop\Factory;
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
        $this->loop = Factory::create();
        if ($this->params->get('rpc')) {
            $this->enableRpc();
        }
    }

    /**
     * Sync all objects
     *
     * Still a prototype
     *
     * USAGE
     *
     * icingacli vsphere task sync [--vCenterId <id>] [--rpc]
     */
    public function syncAction()
    {
        $this->loop->futureTick(function () {
            try {
                (new SyncRunner($this->requireVCenter()))
                    ->run($this->loop)
                    ->otherwise(function () {
                        exit(1);
                    });
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
                // echo $e->getTraceAsString();
                $this->loop->stop();
            } catch (\Error $e) {
                Logger::error($e->getMessage());
                // echo $e->getTraceAsString();
                $this->loop->stop();
            }
        });
        try {
            $this->loop->run();
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }

    public function initializeAction()
    {
        $this->loop->futureTick(function () {
            try {
                $this->requireVCenterServer()->initialize();
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
            } catch (\Error $e) {
                Logger::error($e->getMessage());
            }
            $this->loop->stop();
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
}
