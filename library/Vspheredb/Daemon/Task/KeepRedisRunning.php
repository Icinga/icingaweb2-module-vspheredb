<?php

namespace Icinga\Module\Vspheredb\Daemon\Task;

class KeepRedisRunning extends KeepProcessRunning
{
    public function __construct($configFile, $binary = '/usr/bin/redis-server')
    {
        $this->arguments[] = $configFile;
        $this->command = $binary;
    }
}
