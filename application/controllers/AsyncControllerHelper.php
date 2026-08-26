<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Configuration;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use React\EventLoop\Loop;

use function React\Async\await;
use function React\Promise\Timer\timeout;

trait AsyncControllerHelper
{
    /** @var RemoteClient */
    protected $remoteClient;

    /**
     * Call the daemon synchronously
     *
     * The RPC connection is shared by all calls made while handling a single
     * request and is closed once, on shutdown, so that ReactPHP does not keep
     * an active stream watcher after the response has been sent.
     *
     * @param string $method
     * @param mixed[] $params
     * @param int $timeout
     *
     * @return mixed
     */
    protected function syncRpcCall($method, $params = [], $timeout = 30)
    {
        return await(timeout($this->remoteClient()->request($method, $params), $timeout));
    }

    /**
     * @return RemoteClient
     */
    protected function remoteClient()
    {
        if ($this->remoteClient === null) {
            $this->remoteClient = new RemoteClient(Configuration::getSocketPath(), $this->loop());
            register_shutdown_function(function () {
                if ($this->remoteClient !== null) {
                    $this->remoteClient->close();
                }
            });
        }

        return $this->remoteClient;
    }

    protected function loop()
    {
        return Loop::get();
    }
}
