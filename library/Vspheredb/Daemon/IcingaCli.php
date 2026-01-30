<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterTrait;
use gipfl\Process\FinishedProcessState;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class IcingaCli
{
    use EventEmitterTrait;

    /** @var IcingaCliRunner */
    protected IcingaCliRunner $runner;

    protected array $arguments = [];

    /** @var ?LoopInterface */
    protected ?LoopInterface $loop = null;

    public function __construct(?IcingaCliRunner $runner = null)
    {
        if ($runner === null) {
            $runner = IcingaCliRunner::forArgv();
        }
        $this->runner = $runner;
        $this->init();
    }

    /**
     * @return void
     */
    protected function init(): void
    {
        // Override this if you want.
    }

    /**
     * @param array $arguments
     *
     * @return $this
     */
    public function setArguments(array $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param LoopInterface $loop
     *
     * @return PromiseInterface
     */
    public function run(LoopInterface $loop): PromiseInterface
    {
        $this->loop = $loop;
        $process = $this->runner->command($this->getArguments());
        $canceller = function () use ($process) {
            // TODO: first soft, then hard
            $process->terminate();
        };
        $deferred = new Deferred($canceller);

        $process->on('exit', function ($exitCode, $termSignal) use ($deferred) {
            $state = new FinishedProcessState($exitCode, $termSignal);
            if ($state->succeeded()) {
                $deferred->resolve(null);
            } else {
                $deferred->reject(new \RuntimeException($state->getReason()));
            }
        });
        $process->start($loop);
        $this->emit('start', [$process]);

        return $deferred->promise();
    }
}
