<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use gipfl\Process\FinishedProcessState;
use gipfl\Process\ProcessKiller;
use gipfl\Protocol\JsonRpc\Handler\NamespacedPacketHandler;
use gipfl\Protocol\JsonRpc\JsonRpcConnection;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\LogProxy;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Stream\Util;
use RuntimeException;
use function React\Promise\Timer\timeout;

class DbProcessRunner implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var LoopInterface $loop */
    protected $loop;

    /** @var LoggerInterface */
    protected $logger;

    /** @var JsonRpcConnection */
    protected $rpc;

    /** @var LogProxy */
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
            $process = $this->process;
            $this->process = null;
            if ($process->isRunning()) {
                ProcessKiller::terminateProcess($process, $this->loop, 2);
            }
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
        if ($this->rpc === null) {
            throw new RuntimeException('Process RPC is not ready');
        }

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
        /** @var Deferred $deferred */
        $deferred = $next[0];

        $this->rpc->request($next[1], $next[2])->then(function ($result) use ($deferred) {
            $deferred->resolve($result);
            $this->scheduleNextRequest();
        }, function ($e) use ($deferred) {
            $this->scheduleNextRequest();
            $deferred->reject($e);
        });
    }

    protected function rejectQueue(\Exception $e)
    {
        foreach ($this->queue as $entry) {
            $entry[0]->reject($e);
        }
        $this->queue = [];
    }

    public function run(LoopInterface $loop)
    {
        if ($this->process) {
            throw new RuntimeException('Process is already running');
        }
        $this->loop = $loop;
        $command = new IcingaCliRpc();
        $command->setArguments(['vspheredb', 'db', 'run', '--rpc', '--debug']);
        $command->on('start', function (Process $process) {
            $this->process = $process;
            $process->on('exit', function ($exitCode, $termSignal) {
                // If there is no process, we have been stopped
                if ($this->process) {
                    $message = (new FinishedProcessState($exitCode, $termSignal))->getReason();
                    $this->removeProcess();
                    $this->rejectQueue(new \Exception($message));
                    $this->logger->error($message);
                    $this->emit('error', [new \Exception($message)]);
                }
            });
        });
        Util::forwardEvents($command, $this, ['error']);

        $this->logProxy = new LogProxy($this->logger);
        $this->logProxy->setPrefix("[db] ");
        $promise = timeout($command->rpc(), 10, $loop)->then(function (JsonRpcConnection $rpc) {
            $handler = new NamespacedPacketHandler();
            $handler->registerNamespace('logger', $this->logProxy);
            $rpc->setHandler($handler);
            $this->rpc = $rpc;
        });

        $command->run($loop);

        return $promise;
    }
}
