<?php

namespace Icinga\Module\Vsphere\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Vsphere\Api;

class CommandBase extends Command
{
    private $api;

    protected function api()
    {
        if ($this->api === null) {
            $this->api = new Api(
                $this->params->getRequired('vhost'),
                $this->params->getRequired('username'),
                $this->params->getRequired('password')
            );
        }

        return $this->api;
    }
}
