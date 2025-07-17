<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use Icinga\Module\Vspheredb\Polling\RestApi;
use React\Promise\PromiseInterface;

interface RestApiTask
{
    public function run(RestApi $api): PromiseInterface;
}
