<?php

namespace Icinga\Module\Vspheredb\Daemon\Task;

use React\EventLoop\LoopInterface;
use React\Promise\Promise;

interface TaskInterface
{
    /**
     * @return Promise
     */
    public function terminate();

    public function __invoke(LoopInterface $loop);
}
