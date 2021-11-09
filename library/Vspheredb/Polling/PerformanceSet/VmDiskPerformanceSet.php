<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class VmDiskPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'VirtualDisk';
    protected $objectType = 'VirtualMachine';
    protected $countersGroup = 'virtualDisk';
    protected $counters = [
        // 'busResets', -> not per instance
        // 'commandsAborted',  -> not per instance
        'numberReadAveraged', // rate, average
        'numberWriteAveraged',
        'readLatencyUS', // rate, latest, microseconds
        'writeLatencyUS',
        'totalReadLatency', // absolute, average, milliseconds
        'totalWriteLatency',

        // small is better, less than 64 LBNs are skipped in order to access the requested data
        'smallSeeks',  // absolute, latest
        'mediumSeeks', // Number of seeks during the interval that were between 64 and 8192 LBNs apart
        'largeSeeks',  // Number of seeks during the interval that were greater than 8192 LBNs apart

        'read', // rate, kiloBytesPerSecond, average
        'write',

        // Average number of outstanding read (write) requests:
        'readOIO', // absolute, latest
        'writeOIO',
        // commands, usage
    ];
    // 'maxTotalLatency', // > 20ms BAD -> ist nicht hier
}
