<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
