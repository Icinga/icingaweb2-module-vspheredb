<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmNetworkPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'VirtualNetworkAdapter';
    protected $objectType = 'VirtualMachine';
    protected $countersGroup = 'net';
    protected $counters = [
        'bytesRx', // rate / average / kiloBytesPerSecond
        'bytesTx',
        'packetsRx',
        'packetsTx',
        'broadcastRx',
        'broadcastTx',
        'multicastRx',
        'multicastTx',
        'droppedRx',
        'droppedTx',
    ];
}
