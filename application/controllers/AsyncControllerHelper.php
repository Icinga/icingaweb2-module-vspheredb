<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Configuration;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

use function React\Async\await;
use function React\Promise\Timer\timeout;

trait AsyncControllerHelper
{
    /** @var ?RemoteClient */
    protected ?RemoteClient $remoteClient = null;

    /**
     * @param string $method
     * @param array $params
     * @param ?float $timeout
     *
     * @return mixed
     */
    protected function syncRpcCall(string $method, array $params = [], ?float $timeout = 30): mixed
    {
        return await(timeout($this->remoteClient()->request($method, $params), $timeout));
    }

    /**
     * @return RemoteClient
     */
    protected function remoteClient(): RemoteClient
    {
        return $this->remoteClient ??= new RemoteClient(Configuration::getSocketPath(), $this->loop());
    }

    /**
     * @return LoopInterface
     */
    protected function loop(): LoopInterface
    {
        return Loop::get();
    }
}
