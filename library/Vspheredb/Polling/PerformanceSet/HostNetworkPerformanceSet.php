<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
