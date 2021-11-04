<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\Curl\CurlAsync;
use gipfl\Log\Logger;
use gipfl\RpcDaemon\Connections;
use gipfl\RpcDaemon\ControlSocket;
use gipfl\RpcDaemon\RpcContextConnections;
use gipfl\RpcDaemon\RpcContextProcess;
use gipfl\RpcDaemon\RpcContextSystem;
use gipfl\RpcDaemon\UnixSocketInspection;
use Icinga\Module\Vspheredb\Polling\ApiConnectionHandler;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

class RemoteApi
{
    /** @var UnixSocketInspection */
    protected $unixSocketInspection;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Connections */
    protected $connections;

    /** @var LoopInterface */
    protected $loop;

    /** @var ControlSocket */
    protected $controlSocket;

    /** @var ApiConnectionHandler */
    protected $apiConnectionHandler;
    /**
     * @var CurlAsync
     */
    protected $curl;

    public function __construct(
        ApiConnectionHandler $apiConnectionHandler,
        CurlAsync $curl,
        LoopInterface        $loop,
        LoggerInterface      $logger
    ) {
        $this->apiConnectionHandler = $apiConnectionHandler;
        $this->unixSocketInspection = new UnixSocketInspection();
        $this->logger = $logger;
        $this->loop = $loop;
        $this->curl = $curl;
    }

    public function run($socketPath, LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->connections = new Connections($this->loop);
        $this->initializeControlSocket($socketPath);
    }

    /**
     * @return Connections
     */
    public function connections()
    {
        if ($this->connections === null) {
            throw new \RuntimeException('Cannot get RemoteApi Connections, haven\'t been started yet');
        }
        return $this->connections;
    }

    protected function initializeControlSocket($path)
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Control socket path expected, got none');
        }
        $this->logger->info("Launching control socket in $path");
        $socket = new ControlSocket($path);
        $socket->run($this->loop);
        $this->addSocketEventHandlers($socket);
        $this->controlSocket = $socket;
    }

    protected function addSocketEventHandlers(ControlSocket $socket)
    {
        $socket->on('connection', function (ConnectionInterface $connection) {
            $peer = $this->unixSocketInspection->getPeerInfo($connection);
            $contexts = [
                // new RpcContextProcess($peer, $this, $this->loop), // Daemon!
                new RpcContextConnections($peer, $this->connections),
                new RpcContextSystem($peer),
                new RpcContextVsphere($this->apiConnectionHandler, $peer),
                new RpcContextInfluxDb($this->curl, $this->loop, $this->logger, $peer),
                new RpcContextCurl($this->curl, $peer),
            ];
            if ($this->logger instanceof Logger) {
                $contexts[] = new RpcContextLogger($this->logger, $peer);
            }
            $this->connections->addIncomingConnection($connection, $contexts);
        });
        $socket->on('error', function (Exception $error) {
            // Connection error, Socket remains functional
            $this->logger->error($error->getMessage());
        });
    }
}
