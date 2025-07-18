<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Ramsey\Uuid\UuidInterface;

interface CounterLookup
{
    /**
     * @param UuidInterface $vCenterUuid
     * @return array
     */
    public function fetchTags(?UuidInterface $vCenterUuid = null);

    /**
     * Hint: instance = '*' -> all instances, instance = '' -> aggregated
     *
     * @param UuidInterface $vCenterUuid
     * @return array
     */
    public function fetchRequiredMetricInstances(?UuidInterface $vCenterUuid = null);
}
