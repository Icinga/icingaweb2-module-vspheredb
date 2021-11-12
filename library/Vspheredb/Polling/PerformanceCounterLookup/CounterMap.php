<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceCounterLookup;

use Icinga\Module\Vspheredb\Polling\PerformanceSet\PerformanceSet;
use Ramsey\Uuid\UuidInterface;

abstract class CounterMap
{
    public static function fetchCounters($db, PerformanceSet $set, UuidInterface $vCenterUuid)
    {
        $query = $db
            ->select()
            ->from('performance_counter', [
                'v' => 'counter_key',
                'k' => 'name',
            ])
            ->where('vcenter_uuid = ?', $vCenterUuid->getBytes())
            ->where('group_name = ?', $set->getCountersGroup())
            ->where('rollup_type NOT IN (?)', ['maximum', 'minimum'])
            ->where('name IN (?)', $set->getCounters());

        $counters = [];
        foreach ($db->fetchPairs($query) as $key => $name) {
            $counters[(int) $key] = $name;
        }

        return $counters;
    }
}
