<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\Curl\CurlAsync;
use gipfl\Log\Logger;
use gipfl\Protocol\JsonRpc\Error;
use gipfl\Protocol\JsonRpc\Handler\FailingPacketHandler;
use gipfl\Protocol\JsonRpc\Handler\NamespacedPacketHandler;
use gipfl\Protocol\JsonRpc\JsonRpcConnection;
use gipfl\Protocol\NetString\StreamWrapper;
use gipfl\Socket\UnixSocketInspection;
use gipfl\Socket\UnixSocketPeer;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceCurl;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceInfluxDb;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceLogger;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceSystem;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceVsphere;
use Icinga\Module\Vspheredb\Polling\ApiConnectionHandler;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use function posix_getegid;
use function posix_getgrgid;

class RemoteApi
{
    /** @var LoggerInterface */
    protected $logger;

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
        $this->logger = $logger;
        $this->loop = $loop;
        $this->curl = $curl;
    }

    public function run($socketPath, LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->initializeControlSocket($socketPath);
    }

    protected function initializeControlSocket($path)
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Control socket path expected, got none');
        }
        $this->logger->info("[socket] launching control socket in $path");
        $socket = new ControlSocket($path);
        $socket->run($this->loop);
        $this->addSocketEventHandlers($socket);
        $this->controlSocket = $socket;
    }

    protected function isAllowed(UnixSocketPeer $peer)
    {
        if ($peer->getUid() === 0) {
            return true;
        }
        $myGid = posix_getegid();
        $peerGid = $peer->getGid();
        if ($peerGid === $myGid) {
            return true;
        }
        $additionalGroups = posix_getgrgid(posix_getegid())['members'];
        foreach ($additionalGroups as $groupName) {
            $gid = posix_getgrnam($groupName)['gid'];
            if ($gid === $peerGid) {
                return true;
            }
        }

        return false;
    }

    protected function addSocketEventHandlers(ControlSocket $socket)
    {
        $socket->on('connection', function (ConnectionInterface $connection) {
            $jsonRpc = new JsonRpcConnection(new StreamWrapper($connection));
            $jsonRpc->setLogger($this->logger);

            $peer = UnixSocketInspection::getPeer($connection);
            if (!$this->isAllowed($peer)) {
                $jsonRpc->setHandler(new FailingPacketHandler(new Error(Error::METHOD_NOT_FOUND, [
                    sprintf('%s is not allowed to control this socket', $peer->getUsername())
                ])));
                $this->loop->addTimer(10, function () use ($connection) {
                    $connection->close();
                });
                return;
            }

            $handler = new NamespacedPacketHandler();
            $handler->registerNamespace('system', new RpcNamespaceSystem());
            $handler->registerNamespace('vsphere', new RpcNamespaceVsphere($this->apiConnectionHandler));
            $handler->registerNamespace('influxdb', new RpcNamespaceInfluxDb($this->curl, $this->loop, $this->logger));
            $handler->registerNamespace('curl', new RpcNamespaceCurl($this->curl));
            if ($this->logger instanceof Logger) {
                $handler->registerNamespace('logger', new RpcNamespaceLogger($this->logger));
            }
            $jsonRpc->setHandler($handler);
        });
        $socket->on('error', function (Exception $error) {
            // Connection error, Socket remains functional
            $this->logger->error($error->getMessage());
        });
    }
}
