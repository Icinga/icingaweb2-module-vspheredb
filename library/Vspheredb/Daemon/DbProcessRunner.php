<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use gipfl\Protocol\JsonRpc\Connection;
use Icinga\Module\Vspheredb\Rpc\LogProxy;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Stream\Util;

class DbProcessRunner implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var LoopInterface $loop */
    protected $loop;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Connection */
    protected $rpc;

    protected $logProxy;

    /** @var Process */
    protected $process;

    protected $queue = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function stop()
    {
        if ($this->process) {
            $this->process->close();
        }
    }

    protected function removeProcess()
    {
        $this->process = null;
        $this->rpc = null;
    }

    public function request($method, $params = [])
    {
        // return $this->rpc->request($method, $params);
        $deferred = new Deferred();
        $this->queue[] = [$deferred, $method, $params];
        $this->scheduleNextRequest();
        return $deferred->promise();
    }

    protected function scheduleNextRequest()
    {
        $this->loop->futureTick(function () {
            $this->sendNextRequest();
        });
    }

    protected function sendNextRequest()
    {
        if (empty($this->queue)) {
            return;
        }
        $next = array_shift($this->queue);
        $deferred = $next[0];

        $this->rpc->request($next[1], $next[2])->then(function ($result) use ($deferred) {
            $deferred->resolve($result);
            $this->scheduleNextRequest();
        }, function ($e) use ($deferred) {
            $this->scheduleNextRequest();
            $deferred->reject($e);
        });
    }

    public function run(LoopInterface $loop)
    {
        if ($this->process) {
            throw new \RuntimeException('Process is already running');
        }
        $this->loop = $loop;
        $command = new IcingaCliRpc();
        $command->setArguments(['vspheredb', 'db', 'run']);
        $command->on('start', function (Process $process) {
            $this->process = $process;
            $process->on('exit', function () {
                $this->removeProcess();
            });
            $this->emit('ready'); // TODO: once DB is connected
        });
        Util::forwardEvents($command, $this, ['error']);

        $logProxy = new LogProxy($this->logger);
        $logProxy->setPrefix("[db] ");
        $command->rpc()->setHandler($this->logProxy, 'logger');

        $this->rpc = $command->rpc();
        return $command->run($loop);
    }
}
