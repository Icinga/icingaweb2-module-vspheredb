<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\DbObject\VCenter;

class AlarmHeatmap extends EventHeatmapCalendars
{
    public static function create(VCenter $vCenter, $baseUrl)
    {
        $db = $vCenter->getDb();

        $events = $db->fetchPairs(
            $db->select()
                ->from('alarm_history', [
                    // TODO: / 86400 + offset
                    'day' => 'DATE(FROM_UNIXTIME(ts_event_ms / 1000))',
                    'cnt' => 'COUNT(*)'
                ])->group('day')
        );

        return new static($events, $baseUrl);
    }
}
