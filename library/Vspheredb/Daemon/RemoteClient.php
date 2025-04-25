<?php

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

    /** @var JsonRpcConnection */
    protected $connection;

    /** @var LoopInterface */
    protected $loop;

    protected $pendingConnection;

    public function __construct($path, LoopInterface $loop)
    {
        $this->path = $path;
        $this->loop = $loop;
    }

    public function request($method, $params = null)
    {
        return $this->connection()->then(function (JsonRpcConnection $connection) use ($method, $params) {
            return $connection->request($method, $params);
        });
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
        $connected = function (ConnectionInterface $connection) {
            $jsonRpc = new JsonRpcConnection(new StreamWrapper($connection));
            $this->connection = $jsonRpc;
            $this->pendingConnection = null;
            $connection->on('close', function () {
                $this->connection = null;
            });

            return $jsonRpc;
        };

        return $this->pendingConnection = $connector
            ->connect('unix://' . $this->path)
            ->then($connected);
    }
}
