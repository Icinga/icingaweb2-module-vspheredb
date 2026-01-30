<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
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
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceDbProxy;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceProcess;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceCurl;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceInfluxDb;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceLogger;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceSystem;
use Icinga\Module\Vspheredb\Daemon\RpcNamespace\RpcNamespaceVsphere;
use Icinga\Module\Vspheredb\Polling\ApiConnectionHandler;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Stream\Util;

use function posix_getegid;

class RemoteApi implements EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var LoopInterface */
    protected LoopInterface $loop;

    /** @var ?ControlSocket */
    protected ?ControlSocket $controlSocket = null;

    /** @var ApiConnectionHandler */
    protected ApiConnectionHandler $apiConnectionHandler;

    /** @var CurlAsync */
    protected CurlAsync $curl;

    /** @var RpcNamespaceDbProxy */
    protected RpcNamespaceDbProxy $rpcNamespaceRpcProxy;

    /**
     * @param ApiConnectionHandler $apiConnectionHandler
     * @param CurlAsync            $curl
     * @param LoopInterface        $loop
     * @param LoggerInterface      $logger
     */
    public function __construct(
        ApiConnectionHandler $apiConnectionHandler,
        CurlAsync $curl,
        LoopInterface $loop,
        LoggerInterface $logger
    ) {
        $this->apiConnectionHandler = $apiConnectionHandler;
        $this->logger = $logger;
        $this->loop = $loop;
        $this->curl = $curl;
        $this->rpcNamespaceRpcProxy = new RpcNamespaceDbProxy('db.');
    }

    /**
     * @param string        $socketPath
     * @param LoopInterface $loop
     *
     * @return void
     */
    public function run(string $socketPath, LoopInterface $loop): void
    {
        $this->loop = $loop;
        $this->initializeControlSocket($socketPath);
    }

    /**
     * @param DbProcessRunner|null $dbProcessRunner
     *
     * @return void
     */
    public function setDbProcessRunner(?DbProcessRunner $dbProcessRunner): void
    {
        $this->rpcNamespaceRpcProxy->setDbProcessRunner($dbProcessRunner);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    protected function initializeControlSocket(string $path): void
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

    /**
     * @param UnixSocketPeer $peer
     *
     * @return bool
     */
    protected function isAllowed(UnixSocketPeer $peer): bool
    {
        if ($peer->getUid() === 0) {
            return true;
        }
        $myGid = posix_getegid();
        $peerGid = $peer->getGid();
        // Hint: $myGid makes also part of id -G, this is the fast lane for those using
        //       php-fpm and the user icingaweb2 (with the very same main group as we have)
        if ($peerGid === $myGid) {
            return true;
        }

        $uid = escapeshellarg($peer->getUid());

        return in_array($myGid, array_map('intval', explode(' ', shell_exec("id -G $uid"))));
    }

    /**
     * @param ControlSocket $socket
     *
     * @return void
     */
    protected function addSocketEventHandlers(ControlSocket $socket): void
    {
        $socket->on('connection', function (ConnectionInterface $connection) {
            $jsonRpc = new JsonRpcConnection(new StreamWrapper($connection));
            $jsonRpc->setLogger($this->logger);

            try {
                $peer = UnixSocketInspection::getPeer($connection);
            } catch (Exception $e) {
                $jsonRpc->setHandler(new FailingPacketHandler(Error::forException($e)));
                $this->loop->addTimer(3, function () use ($connection) {
                    $connection->close();
                });
                return;
            }

            if (!$this->isAllowed($peer)) {
                $jsonRpc->setHandler(new FailingPacketHandler(new Error(Error::METHOD_NOT_FOUND, sprintf(
                    '%s is not allowed to control this socket',
                    $peer->getUsername()
                ))));
                $this->loop->addTimer(10, function () use ($connection) {
                    $connection->close();
                });
                return;
            }

            $rpcProcess = new RpcNamespaceProcess($this->loop);
            Util::forwardEvents($rpcProcess, $this, [RpcNamespaceProcess::ON_RESTART]);
            $handler = new NamespacedPacketHandler();
            $handler->registerNamespace('process', $rpcProcess);
            $handler->registerNamespace('system', new RpcNamespaceSystem());
            $handler->registerNamespace('vsphere', new RpcNamespaceVsphere($this->apiConnectionHandler));
            $handler->registerNamespace('influxdb', new RpcNamespaceInfluxDb($this->curl, $this->loop, $this->logger));
            $handler->registerNamespace('curl', new RpcNamespaceCurl($this->curl));
            $handler->registerNamespace('db', $this->rpcNamespaceRpcProxy);
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
