<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Exception;
use gipfl\Cli\Process;
use gipfl\Log\IcingaWeb\IcingaLogger;
use gipfl\Log\Logger;
use gipfl\Log\Writer\JsonRpcConnectionWriter;
use gipfl\Protocol\JsonRpc\Handler\NamespacedPacketHandler;
use gipfl\Protocol\JsonRpc\JsonRpcConnection;
use gipfl\Protocol\NetString\StreamWrapper;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\DbRunner;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

/**
 * vSphereDB child process, our (synchronous) DB connection
 */
class DbCommand extends Command
{
    public function runAction()
    {
        if (!$this->isRpc()) {
            $this->fail('This is an internal command and should not be called directly');
        }
        $this->loop()->futureTick(function () {
            $this->logger = $this->prepareLogger();
            try {
                Process::setTitle('Icinga::vSphereDB::DB::idle');
                $rpc = $this->prepareJsonRpc($this->loop());
                $this->logger->addWriter(new JsonRpcConnectionWriter($rpc));
                $handler = new NamespacedPacketHandler();
                $handler->registerNamespace('vspheredb', new DbRunner($this->logger));
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                // This allows to flush streams, especially pending log messages
                $this->loop()->addTimer(0.1, function () {
                    $this->stopMainLoop();
                    exit(1);
                });
            }
        });
        $this->loop()->run();
    }

    protected function prepareLogger()
    {
        $logger = new Logger();
        $this->eventuallyFilterLog($logger);
        IcingaLogger::replace($logger);
        return $logger;
    }

    /**
     * Prepares a JSON-RPC Connection on STDIN/STDOUT
     */
    protected function prepareJsonRpc(LoopInterface $loop)
    {
        return new JsonRpcConnection(new StreamWrapper(
            new ReadableResourceStream(STDIN, $loop),
            new WritableResourceStream(STDOUT, $loop)
        ));
    }
}
