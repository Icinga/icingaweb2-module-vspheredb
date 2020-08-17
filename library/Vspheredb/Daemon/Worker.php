<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Icinga\Application\Logger;
use Icinga\Module\Vspheredb\Daemon\Task\TaskInterface;
use React\EventLoop\Factory as Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Stream\ReadableResourceStream;
use SplObjectStorage;

class Worker
{
    /** @var LoopInterface */
    protected $loop;

    protected $pid;

    protected $name;

    protected $tasks;

    protected $stdInBuffer;

    protected $exiting = false;

    public function __construct($name, LoopInterface $loop = null)
    {
        $this->name = $name;
        $this->pid = posix_getpid();
        if ($loop === null) {
            $this->loop = Loop::create();
        } else {
            $this->loop = $loop;
        }
        $this->tasks = new SplObjectStorage();

        $this->moveToOwnProcessGroup();
        $this->setProcessTitle("vspheredb::worker ($name)");
        $this->initializeKeepAliveTimer();
        $this->addSignalHandlers();
        $this->createStdInReader();

        $this->log(sprintf(
            'My process group: %d, my pid: %d',
            \posix_getpgid($this->pid),
            $this->pid
        ));
    }

    protected function moveToOwnProcessGroup()
    {
        if ($this->pid !== posix_getpgid($this->pid)) {
            posix_setpgid($this->pid, $this->pid);
        }
    }

    public function addTask(TaskInterface $task)
    {
        $this->tasks->attach($task);
        $task($this->loop);
    }

    public function removeTask(TaskInterface $task)
    {
        $this->tasks->detach($task);
    }

    public function run()
    {
        $this->loop->run();
    }

    protected function initializeKeepAliveTimer()
    {
        $this->loop->addPeriodicTimer(2, [$this, 'sendKeepAlives']);
    }

    /**
     * @internal
     */
    public function sendKeepAlives()
    {
        // $this->log("worker is still alive");
    }

    protected function createStdInReader()
    {
        $read = new ReadableResourceStream(fopen('php://stdin', 'r+'), $this->loop);
        $read->on('data', function ($chunk) {
            $this->stdInBuffer .= $chunk;
            $this->processStdin();
        });
    }

    protected function processStdin()
    {
        while (false !== ($pos = strpos($this->stdInBuffer, "\n"))) {
            $command = substr($this->stdInBuffer, 0, $pos);
            $this->log("Got Command: $command");
            if ($command === 'exit') {
                $this->log('Got exit, exiting');
                $this->shutdown();
            }

            $this->stdInBuffer = substr($this->stdInBuffer, $pos + 1);
        }
    }

    protected function addSignalHandlers()
    {
        $this->loop->addSignal(SIGTERM, function () {
            $this->sigTerm();
        });
        $this->loop->addSignal(SIGINT, function () {
            $this->sigInt();
        });
    }

    protected function setProcessTitle($title)
    {
        if (PHP_VERSION_ID >= 50500) {
            cli_set_process_title($title);
        }

        return $this;
    }

    public function log($message)
    {
        Logger::info(
            "%s/%d: %s",
            $this->name,
            $this->pid,
            $message
        );
    }

    protected function shutdown()
    {
        if ($this->exiting) {
            return;
        }

        $this->exiting = true;
        $promises = [];
        foreach ($this->tasks as $task) {
            $promises[] = $task->terminate();
        }

        $this->endAfterPromises($promises);
    }

    protected function scheduleUncleanExit()
    {
        $this->loop->addTimer(10, function () {
            $this->log('Unclean exit');
            exit(1);
        });
    }

    /**
     * @param Promise[] $promises
     */
    protected function endAfterPromises($promises)
    {
        $total = count($promises);
        $cleared = 0;
        $failed = 0;
        foreach ($promises as $promise) {
            $promise->then(function () use (&$cleared, $failed, $total) {
                $cleared++;

                if ($cleared + $failed === $total) {
                    $this->reallyExit($failed);
                }
            })->otherwise(function () use ($cleared, &$failed, $total) {
                $failed++;

                if ($cleared + $failed === $total) {
                    $this->reallyExit($failed);
                }
            });
        }
    }

    /**
     * @param int $failed
     */
    protected function reallyExit($failed = 0)
    {
        if ($failed === 0) {
            $this->log('All tasks exited cleanly');
        } else {
            $this->log(sprintf(
                '%d tasks may be still running, exiting anyways',
                $failed
            ));
        }
        $this->loop->stop();
        exit(0);
    }

    /**
     * @api internal
     */
    public function sigTerm()
    {
        $this->log('Got SIGTERM, exiting');
        $this->shutdown();
    }

    /**
     * @api internal
     */
    public function sigInt()
    {
        $this->log('Got SIGINT, exiting');
        $this->shutdown();
    }
}
