<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class HostNetworkPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'HostNetworkAdapter';
    protected $objectType = 'HostSystem';
    protected $countersGroup = 'net';
    protected $counters = [
        // averaged alternative: received, transmitted, usage
        // TODO: evaluate net.usage
        'bytesRx',
        'bytesTx',
        'packetsRx',
        'packetsTx',
        'broadcastRx',
        'broadcastTx',
        'multicastRx',
        'multicastTx',
        'droppedRx',
        'droppedTx',
        'errorsRx',
        'errorsTx',
    ];
}
