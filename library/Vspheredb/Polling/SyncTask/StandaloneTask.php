<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\Polling\VsphereApi;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;

interface StandaloneTask
{
    public function run(VsphereApi $api, LoggerInterface $logger): PromiseInterface;
}
