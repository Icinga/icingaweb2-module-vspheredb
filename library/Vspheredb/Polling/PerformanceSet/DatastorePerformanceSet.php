<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

class DatastorePerformanceSet extends DefaultPerformanceSet
{
    protected ?string $name = 'Datastore';

    protected ?string $objectType = 'Datastore';

    protected ?string $countersGroup = 'datastore';

    protected ?array $counters = [
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
