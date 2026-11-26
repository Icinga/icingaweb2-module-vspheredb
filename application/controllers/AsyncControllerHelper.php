<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Configuration;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use React\EventLoop\Loop;
use Throwable;

trait AsyncControllerHelper
{
    /** @var RemoteClient */
    protected $remoteClient;

    /**
     * Call the daemon synchronously
     *
     * @param string $method
     * @param mixed[] $params
     * @param float $timeout Timeout in seconds
     *
     * @return mixed
     *
     * @throws Throwable If the call fails
     */
    protected function syncRpcCall($method, $params = [], $timeout = RemoteClient::DEFAULT_REQUEST_TIMEOUT)
    {
        return $this->remoteClient()->requestSync($method, $params, $timeout);
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
