<?php

namespace Icinga\Module\Vspheredb\Daemon\Task;

use Evenement\EventEmitter;
use Icinga\Application\Logger;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Vspheredb\Daemon\WatchDog;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

abstract class KeepProcessRunning extends EventEmitter implements TaskInterface
{
    /** @var string */
    protected $command;

    protected $arguments = [];

    /** @var WatchDog */
    protected $watchDog;

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

            $this->watchDog->on('start', function () {
                $this->log('Got "start" from WatchDog, emitting "ready"');
                $this->emit('ready');
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
