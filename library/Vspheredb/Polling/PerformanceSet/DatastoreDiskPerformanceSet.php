<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class DatastoreDiskPerformanceSet extends DefaultPerformanceSet
{
    protected ?string $name = 'DatastoreDisk';

    protected ?string $objectType = 'Datastore';

    protected ?string $countersGroup = 'disk';

    protected ?array $counters = [
        'capacity',
        'used',
        'provisioned',
        'deltaused'
    ];
}
