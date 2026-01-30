<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Exception;
use gipfl\Cli\Process;
use gipfl\Log\IcingaWeb\IcingaLogger;
use gipfl\Log\Logger;
use gipfl\Log\Writer\JsonRpcConnectionWriter;
use gipfl\Protocol\JsonRpc\Handler\JsonRpcHandler;
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
    /**
     * @return void
     */
    public function runAction(): void
    {
        if (!$this->isRpc()) {
            $this->fail('This is an internal command and should not be called directly');
        }
        $this->loop()->futureTick(function () {
            $this->logger = $this->prepareLogger();
            try {
                Process::setTitle('Icinga::vSphereDB::DB::idle');
                $handler = new NamespacedPacketHandler();
                $handler->registerNamespace('db', new DbRunner($this->logger, $this->loop()));
                $rpc = $this->prepareJsonRpc($this->loop(), $handler);
                $this->logger->addWriter(new JsonRpcConnectionWriter($rpc));
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

    /**
     * @return Logger
     */
    protected function prepareLogger(): Logger
    {
        $logger = new Logger();
        $this->eventuallyFilterLog($logger);
        IcingaLogger::replace($logger);
        return $logger;
    }

    /**
     * Prepares a JSON-RPC Connection on STDIN/STDOUT
     *
     * @param LoopInterface  $loop
     * @param JsonRpcHandler $handler
     *
     * @return JsonRpcConnection
     */
    protected function prepareJsonRpc(LoopInterface $loop, JsonRpcHandler $handler): JsonRpcConnection
    {
        return new JsonRpcConnection(new StreamWrapper(
            new ReadableResourceStream(STDIN, $loop),
            new WritableResourceStream(STDOUT, $loop)
        ), $handler);
    }
}
