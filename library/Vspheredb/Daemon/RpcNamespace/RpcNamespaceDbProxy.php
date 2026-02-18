<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use Icinga\Module\Vspheredb\Daemon\DbProcessRunner;
use React\Promise\PromiseInterface;
use RuntimeException;

class RpcNamespaceDbProxy
{
    /** @var ?DbProcessRunner */
    protected ?DbProcessRunner $runner = null;

    /** @var string */
    protected string $prefix;

    /**
     * @param string $prefix
     */
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param DbProcessRunner|null $runner
     *
     * @return void
     */
    public function setDbProcessRunner(?DbProcessRunner $runner): void
    {
        $this->runner = $runner;
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return PromiseInterface
     */
    public function __call(string $method, array $params)
    {
        if (preg_match('/Request$/', $method)) {
            if ($this->runner === null) {
                throw new RuntimeException('DB runner is not ready');
            }

            return $this->runner->request($this->prefix . substr($method, 0, -7), $params);
        }

        throw new RuntimeException('Got no such method: ' . $method);
    }
}
