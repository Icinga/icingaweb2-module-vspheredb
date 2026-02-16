<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmNetworkPerformanceSet extends DefaultPerformanceSet
{
    protected ?string $name = 'VirtualNetworkAdapter';

    protected ?string $objectType = 'VirtualMachine';

    protected ?string $countersGroup = 'net';

    protected ?array $counters = [
        'bytesRx', // rate / average / kiloBytesPerSecond
        'bytesTx',
        'packetsRx',
        'packetsTx',
        'broadcastRx',
        'broadcastTx',
        'multicastRx',
        'multicastTx',
        'droppedRx',
        'droppedTx'
    ];
}
