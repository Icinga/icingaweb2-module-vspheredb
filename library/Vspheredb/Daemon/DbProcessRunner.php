<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use gipfl\Process\FinishedProcessState;
use gipfl\Process\ProcessKiller;
use gipfl\Protocol\JsonRpc\Handler\NamespacedPacketHandler;
use gipfl\Protocol\JsonRpc\JsonRpcConnection;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\LogProxy;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\Util;
use RuntimeException;

use function React\Promise\Timer\timeout;

class DbProcessRunner implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var ?LoopInterface $loop */
    protected ?LoopInterface $loop = null;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var ?JsonRpcConnection */
    protected ?JsonRpcConnection $rpc = null;

    /** @var ?LogProxy */
    protected ?LogProxy $logProxy = null;

    /** @var ?Process */
    protected ?Process $process = null;

    /** @var array */
    protected array $queue = [];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function stop(): void
    {
        if ($this->process) {
            $process = $this->process;
            $this->process = null;
            if ($process->isRunning()) {
                ProcessKiller::terminateProcess($process, $this->loop, 2);
            }
        }
    }

    /**
     * @return void
     */
    protected function removeProcess(): void
    {
        $this->process = null;
        $this->rpc = null;
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @return PromiseInterface
     */
    public function request(string $method, array $params = []): PromiseInterface
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

    /**
     * @return void
     */
    protected function scheduleNextRequest(): void
    {
        $this->loop->futureTick(function () {
            $this->sendNextRequest();
        });
    }

    /**
     * @return void
     */
    protected function sendNextRequest(): void
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

    /**
     * @param Exception $e
     *
     * @return void
     */
    protected function rejectQueue(Exception $e): void
    {
        foreach ($this->queue as $entry) {
            $entry[0]->reject($e);
        }
        $this->queue = [];
    }

    /**
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     */
    public function run(LoopInterface $loop): PromiseInterface
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
