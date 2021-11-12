<?php

namespace Icinga\Module\Vspheredb\Daemon\RpcNamespace;

use gipfl\LinuxHealth\Cpu;
use gipfl\LinuxHealth\Network;

class RpcNamespaceSystem
{
    /**
     * @return object
     */
    public function cpuCountersRequest()
    {
        return (object) Cpu::getCounters();
    }

    /**
     * @return object
     */
    public function interfaceCountersRequest()
    {
        return (object) Network::getInterfaceCounters();
    }
}
