<?php

namespace Icinga\Module\Vspheredb\Daemon\Task;

use Evenement\EventEmitter;
use Icinga\Application\Logger;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Vspheredb\Daemon\WatchDog;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Stream\ThroughStream;

abstract class KeepProcessRunning extends EventEmitter implements TaskInterface
{
    /** @var string */
    protected $command;

    protected $arguments = [];

    /** @var WatchDog */
    protected $watchDog;

    /** @var ThroughStream */
    protected $stdout;

    /** @var ThroughStream */
    protected $stderr;

    /**
     * @return string
     * @throws ProgrammingError
     */
    public function getCommand()
    {
        if ($this->command === null) {
            throw new ProgrammingError('Got no command to run');
        }

        return $this->command;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return Promise
     */
    public function terminate()
    {
        return $this->watchDog->terminate();
    }

    public function stdout()
    {
        if ($this->stdout === null) {
            $this->stdout = new ThroughStream();
        }

        return $this->stdout;
    }

    public function stderr()
    {
        if ($this->stderr === null) {
            $this->stderr = new ThroughStream();
        }

        return $this->stderr;
    }

    /**
     * @return WatchDog
     * @throws ProgrammingError
     */
    public function getWatchDog()
    {
        if ($this->watchDog === null) {
            $this->watchDog = new WatchDog(
                $this->getCommand(),
                $this->getArguments()
            );

            $this->watchDog->on('start', function ($pid) {
                $this->log('Got "start" from WatchDog, emitting "ready" for PID ' . $pid);
                $this->emit('ready');

                // Hint: make sure they are initialized, but avoid function call for
                // each event
                $this->stdout();
                $this->stderr();
                $this->watchDog->getProcess()->stdout->on('data', function ($data) {
                    $this->stdout->write($data);
                });

                $this->watchDog->getProcess()->stderr->on('data', function ($data) {
                    $this->stderr->write($data);
                });
            });
        }

        return $this->watchDog;
    }

    public function log($message)
    {
        Logger::info('Keeping Process Running: %s', $message);
    }

    /**
     * @param LoopInterface $loop
     * @throws ProgrammingError
     */
    public function __invoke(LoopInterface $loop)
    {
        $this->getWatchDog()->run($loop);
    }
}
