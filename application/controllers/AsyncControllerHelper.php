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
     * All calls made while handling one request share the same RPC connection.
     * A shutdown function closes it so that ReactPHP keeps no stream watcher
     * active after the response has been sent.
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
            // On shutdown, React resumes the event loop that the first await()
            // suspended. That loop blocks while the socket is open. Shutdown
            // functions run in registration order, so register this one before
            // the first await() to close the socket before the loop resumes.
            register_shutdown_function(function (): void {
                $this->remoteClient->close();
            });
        }

        return $this->remoteClient;
    }

    protected function loop()
    {
        return Loop::get();
    }
}
