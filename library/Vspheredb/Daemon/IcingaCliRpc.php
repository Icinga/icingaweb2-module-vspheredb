<?php

namespace Icinga\Module\Vspheredb\Daemon;

use Exception;
use gipfl\Protocol\JsonRpc\JsonRpcConnection;
use gipfl\Protocol\NetString\StreamWrapper;
use React\ChildProcess\Process;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

class IcingaCliRpc extends IcingaCli
{
    /** @var IcingaCliRunner */
    protected $runner;

    /** @var JsonRpcConnection */
    protected $rpc;

    /** @var Deferred */
    protected $waitingForRpc;

    protected $arguments = [];

    protected function init()
    {
        $this->on('start', function (Process $process) {
            $netString = new StreamWrapper(
                $process->stdout,
                $process->stdin
            );
            $netString->on('error', function (Exception $e) {
                if ($this->waitingForRpc) {
                    $this->waitingForRpc->reject($e);
                }
                $this->emit('error', [$e]);
            });
            $this->rpc = new JsonRpcConnection($netString);
            if ($deferred = $this->waitingForRpc) {
                $this->waitingForRpc = null;
                $deferred->resolve($this->rpc);
            }
        });
    }

    /**
     * @return PromiseInterface <Connection>
     */
    public function rpc()
    {
        if (! $this->waitingForRpc) {
            $this->waitingForRpc = new Deferred();
        }

        if ($this->rpc) {
            $this->loop->futureTick(function () {
                if ($this->rpc && $deferred = $this->waitingForRpc) {
                    $this->waitingForRpc = null;
                    $deferred->resolve($this->rpc);
                }
            });
        }

        return $this->waitingForRpc->promise();
    }
}
