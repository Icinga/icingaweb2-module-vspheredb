<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class HostNetworkPerformanceSet extends DefaultPerformanceSet
{
    protected ?string $name = 'HostNetworkAdapter';

    protected ?string $objectType = 'HostSystem';

    protected ?string $countersGroup = 'net';

    protected ?array $counters = [
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
