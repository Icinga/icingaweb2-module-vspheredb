<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Exception;
use gipfl\Cli\Process;
use gipfl\Cli\Tty;
use gipfl\Log\IcingaWeb\IcingaLogger;
use gipfl\Log\Logger;
use gipfl\Protocol\JsonRpc\Connection;
use gipfl\Protocol\NetString\StreamWrapper;
use Icinga\Module\Vspheredb\Daemon\DbRunner;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

/**
 * vSphereDB child process, our (synchronous) DB connection
 */
class DbCommand extends Command
{
    /**
     * icingacli vspheredb db run
     */
    public function runAction()
    {
        $this->loop()->futureTick(function () {
            $this->logger = $this->prepareLogger();
            try {
                Process::setTitle('Icinga::vSphereDB::DB');
                $rpc = $this->prepareJsonRpc($this->loop());
                $this->logger->error('test');
                $rpc->setHandler(new DbRunner($this->logger), 'vspheredb');
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
        if (Tty::isSupported()) {
            $stdin = (new Tty($loop))->setEcho(false)->stdin();
        } else {
            $stdin = new ReadableResourceStream(STDIN, $loop);
        }
        $netString = new StreamWrapper($stdin, new WritableResourceStream(STDOUT, $loop));
        $rpc = new Connection();
        $rpc->handle($netString);
        return $rpc;
    }
}
