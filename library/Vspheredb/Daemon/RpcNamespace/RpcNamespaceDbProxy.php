<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use Icinga\Module\Vspheredb\Daemon\DbProcessRunner;

class RpcNamespaceDbProxy
{
    /** @var ?DbProcessRunner */
    protected $runner;

    /** @var string */
    protected $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function setDbProcessRunner(?DbProcessRunner $runner)
    {
        $this->runner = $runner;
    }

    public function __call($method, $params)
    {
        if (preg_match('/Request$/', $method)) {
            if ($this->runner === null) {
                throw new \RuntimeException('DB runner is not ready');
            }

            return $this->runner->request($this->prefix . substr($method, 0, -7), $params);
        }

        throw new \RuntimeException('Got no such method: ' . $method);
    }
}
