<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Protocol\JsonRpc\JsonRpcConnection;
use gipfl\Protocol\NetString\StreamWrapper;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\UnixConnector;

use function React\Promise\resolve;

class RemoteClient
{
    protected $path;

    /** @var ?JsonRpcConnection */
    protected $connection;

    /** @var LoopInterface */
    protected $loop;

    /** @var ?\React\Promise\PromiseInterface<JsonRpcConnection> */
    protected $pendingConnection;

    public function __construct($path, LoopInterface $loop)
    {
        $this->path = $path;
        $this->loop = $loop;
    }

    /**
     * @param string $method
     * @param mixed $params
     *
     * @return \React\Promise\PromiseInterface<mixed>
     */
    public function request($method, $params = null)
    {
        return $this->connection()->then(function (JsonRpcConnection $connection) use ($method, $params) {
            return $connection->request($method, $params);
        });
    }

    /**
     * Close the JSON-RPC connection and deregister its event loop watchers
     *
     * The next request opens a new connection. Calling this method again has no
     * effect.
     *
     * @return void
     */
    public function close(): void
    {
        $connection = $this->connection;
        $this->connection = null;
        $this->pendingConnection = null;

        if ($connection !== null) {
            $connection->close();
        }
    }

    public function notify($method, $params = null)
    {
        return $this->connection()->then(function (JsonRpcConnection $connection) use ($method, $params) {
            $connection->notification($method, $params);
        });
    }

    protected function connection()
    {
        if ($this->connection === null) {
            if ($this->pendingConnection === null) {
                return $this->connect();
            } else {
                return $this->pendingConnection;
            }
        } else {
            return resolve($this->connection);
        }
    }

    protected function connect()
    {
        $connector = new UnixConnector($this->loop);
        $connected = function (ConnectionInterface $connection): JsonRpcConnection {
            $jsonRpc = new JsonRpcConnection(new StreamWrapper($connection));
            $this->connection = $jsonRpc;
            $connection->on('close', function () use ($jsonRpc): void {
                // A late close event of an old socket must not drop its replacement.
                if ($this->connection === $jsonRpc) {
                    $this->connection = null;
                    $this->pendingConnection = null;
                }
            });

            return $jsonRpc;
        };

        $pendingConnection = $connector
            ->connect('unix://' . $this->path)
            ->then($connected);
        // UnixConnector may settle synchronously. Store the promise before
        // attaching the cleanup, so the cleanup cannot run before the store.
        $this->pendingConnection = $pendingConnection;
        $clearPendingConnection = function () use ($pendingConnection): void {
            if ($this->pendingConnection === $pendingConnection) {
                $this->pendingConnection = null;
            }
        };
        $pendingConnection->then($clearPendingConnection, $clearPendingConnection);

        return $pendingConnection;
    }
}
