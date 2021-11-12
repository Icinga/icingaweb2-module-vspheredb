<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class DatastorePerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'Datastore';
    protected $objectType = 'Datastore';
    protected $countersGroup = 'datastore';
    protected $counters = [
        'read',
        'write',
        'datastoreReadBytes',
        'datastoreWriteBytes',
        'datastoreReadIops',
        'datastoreWriteIops',
        'totalReadLatency',
        'totalWriteLatency',
    ];
}
