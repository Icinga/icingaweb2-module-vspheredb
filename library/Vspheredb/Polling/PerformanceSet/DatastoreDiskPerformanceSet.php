<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class DatastoreDiskPerformanceSet extends DefaultPerformanceSet
{
    protected $name = 'DatastoreDisk';
    protected $objectType = 'Datastore';
    protected $countersGroup = 'disk';
    protected $counters = [
        'capacity',
        'usage',
        'used',
        'provisioned',
        'deltaused',
    ];
}
