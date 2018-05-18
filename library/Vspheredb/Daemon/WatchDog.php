<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitter;
use Icinga\Application\Logger;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\Timer;
use React\Stream\ThroughStream;
use RuntimeException;

class WatchDog extends EventEmitter
{
    /** @var LoopInterface */
    protected $loop;

    protected $command;

    protected $childPid;

    protected $reRunScheduled = false;

    /** @var  Process */
    protected $process;

    protected $args = [];

    protected $exiting = false;

    protected $stdin;

    protected $stdout;

    protected $stderr;

    protected $cwd;

    protected $env;

    public function __construct($command, $args = null)
    {
        $this->command = $command;
        $this->pid = posix_getpid();
        if ($args !== null) {
            $this->args = $args;
        }

        $this->stdout = new ThroughStream();
        $this->stderr = new ThroughStream();
        // $this->stdin->on('data', function ($data) {
        //     $this->process->stdin->write($data);
        // });
    }

    public function setWorkingDir($dir)
    {
        $this->cwd = $dir;

        return $this;
    }

    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    public function terminate()
    {
        $this->exiting = true;

        $killMe = $this->loop->addTimer(2, function () {
            $this->log('Child did not exit, KILLing it');
            $this->process->terminate(SIGKILL);
        });
        $promise = new Promise(
            function ($resolve, $reject) use ($killMe) {
                $this->process->on('exit', function ($exitCode, $termSignal) use ($resolve, $killMe) {
                    $this->loop->cancelTimer($killMe);
                    $this->log('Child exited');
                    $resolve();
                });
                $this->process->terminate();
            },
            function ($resolve, $reject) use (&$socket) {
                $this->log('Child did not exit');
                $this->process->terminate(SIGKILL);
                $reject(new RuntimeException('Process not terminated, sent SIGKILL'));
            }
        );

        return Timer\timeout($promise, 3, $this->loop);
    }

    protected function runAgain($delay = 0)
    {
        if ($this->exiting) {
            return;
        }
        $this->loop->addTimer($delay, function () {
            $this->run();
        });
        if ($this->reRunScheduled) {
            // TODO: Check time issues
            return;
        }

        $this->reRunScheduled = false;
    }

    protected function getArgumentString()
    {
        if (empty($this->args)) {
            return '';
        }

        return ' ' . implode(' ', array_map('escapeshellarg', $this->args));
    }

    public function run(LoopInterface $loop = null)
    {
        if ($loop !== null) {
            $this->loop = $loop;
        }
        $cmd = 'exec ' . $this->command . $this->getArgumentString();
        $this->log("running $cmd");
        $process = new Process($cmd, $this->cwd, $this->env);
        $process->on('error', function (\Exception $e) {
            $delay = 5;
            $this->log(sprintf(
                'Failed to run: %s, restarting in %d seconds',
                $e->getMessage(),
                $delay
            ));
            $this->runAgain($delay);
        });

        $process->on('exit', function ($exitCode, $termSignal) {
            $delay = 5;
            if ($exitCode === null) {
                if ($termSignal === null) {
                    $this->log('died');
                    // $event->setLastExitCode(255);
                } else {
                    $this->log("got terminated with SIGNAL $termSignal");
                    // $event->setLastExitCode(128 + $termSignal);
                }
            } else {
                if ($exitCode === 0) {
                    $delay = 0;
                }
                $this->log("exited with exit code $exitCode");
            }

            if ($this->exiting) {
                //
            } else {
                $this->log("Stopped, restarting in $delay seconds");
                $this->runAgain($delay);
            }
        });

        $process->start($this->loop);
        $this->childPid = $process->getPid();

        // process->stdout->pipe($this->stdout);
        // $process->stderr->pipe($this->stderr);

        // Forward all:
        $this->stdout->on('data', function ($str) {
        //     // $this->stdout->write('data');
            $this->log("Got $str");
        });

//        $this->stdout = new ThroughStream($process->stderr);
/*
 *
        $process->stdout->on('data', function ($str) {
            $this->stdout->write('data');
            // $this->log("Got $str");
        });

        $process->stderr->on('data', function ($str) {
            $this->stderr->write('data');
            // $this->log("ERR: $str");
        });
*/
        $this->process = $process;
        $this->emit('start', [$this->childPid]);
    }

    /**
     * @return ThroughStream
     */
    public function stdout()
    {
        return $this->stdout;
    }

    /**
     * @return ThroughStream
     */
    public function stderr()
    {
        return $this->stderr;
    }

    public function getProcess()
    {
        return $this->process;
    }

    public function log($message)
    {
        if ($this->childPid === null) {
            Logger::info('WatchDog/%d: %s', posix_getppid(), $message);
        } else {
            Logger::info(
                'WatchDog/%d->%d: %s',
                posix_getppid(),
                $this->childPid,
                $message
            );
        }
    }
}
