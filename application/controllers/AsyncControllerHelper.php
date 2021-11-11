<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Clue\React\Block;
use Icinga\Module\Vspheredb\Configuration;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use React\EventLoop\Factory as Loop;

trait AsyncControllerHelper
{
    protected $loop;

    /** @var RemoteClient */
    protected $remoteClient;

    protected function syncRpcCall($method, $params = [], $timeout = 30)
    {
        return Block\await($this->remoteClient()->request($method, $params), $this->loop(), $timeout);
    }

    /**
     * @return RemoteClient
     */
    protected function remoteClient()
    {
        if ($this->remoteClient === null) {
            $this->remoteClient = new RemoteClient(Configuration::getSocketPath(), $this->loop());
        }

        return $this->remoteClient;
    }

    protected function loop()
    {
        // Hint: we're not running this loop right now
        if ($this->loop === null) {
            $this->loop = Loop::create();
        }

        return $this->loop;
    }
}
