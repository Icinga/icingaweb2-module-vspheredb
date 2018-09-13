<?php

namespace Icinga\Module\Vspheredb\Daemon\Task;

class KeepWorkerRunning extends KeepProcessRunning
{
    protected $command;

    public function __construct($action, $argv = null, $arguments = [])
    {
        if ($argv === null) {
            global $argv;
        }
        $this->command = $argv[0];
        $this->arguments = ['vspheredb', 'daemon', $action];
        foreach ($arguments as $arg) {
            $this->arguments[] = $arg;
        }
    }
}
