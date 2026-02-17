<?php

namespace Icinga\Module\Vspheredb\Daemon;

use gipfl\Protocol\JsonRpc\JsonRpcConnection;
use gipfl\Protocol\NetString\StreamWrapper;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\UnixConnector;

use function React\Promise\resolve;

class RemoteClient
{
    /** @var string */
    protected string $path;

    /** @var ?JsonRpcConnection */
    protected ?JsonRpcConnection $connection = null;

    /** @var LoopInterface */
    protected LoopInterface $loop;

    /** @var ?PromiseInterface */
    protected ?PromiseInterface $pendingConnection = null;

    /**
     * @param string        $path
     * @param LoopInterface $loop
     */
    public function __construct(string $path, LoopInterface $loop)
    {
        $this->path = $path;
        $this->loop = $loop;
    }

    /**
     * @param string     $method
     * @param array|null $params
     *
     * @return PromiseInterface
     */
    public function request(string $method, ?array $params = null): PromiseInterface
    {
        return $this->connection()->then(function (JsonRpcConnection $connection) use ($method, $params) {
            return $connection->request($method, $params);
        });
    }

    /**
     * @param string     $method
     * @param array|null $params
     *
     * @return PromiseInterface
     */
    public function notify(string $method, ?array $params = null): PromiseInterface
    {
        return $this->connection()->then(function (JsonRpcConnection $connection) use ($method, $params) {
            $connection->notification($method, $params);
        });
    }

    /**
     * @return PromiseInterface
     */
    protected function connection(): PromiseInterface
    {
        if ($this->connection === null) {
            return $this->pendingConnection ?? $this->connect();
        }

        return resolve($this->connection);
    }

    /**
     * @return PromiseInterface
     */
    protected function connect(): PromiseInterface
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
