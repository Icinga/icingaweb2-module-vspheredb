<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Db;

class VMotionHeatmap extends EventHeatmapCalendars
{
    protected $db;

    protected $query;

    protected $eventType;

    public function __construct(Db $connection, $baseUrl)
    {
        $this->setBaseUrl($baseUrl);
        $this->db = $connection->getDbAdapter();
    }

    public function getQuery()
    {
        if ($this->query === null) {
            $this->query = $this->prepareQuery();
        }

        return $this->query;
    }

    public function filterEventType($type)
    {
        $this->eventType = $type;

        return $this;
    }

    public function filterParent($uuid)
    {
        $this->getQuery()->join(
            ['h' => 'object'],
            $this->db->quoteInto(
                'h.uuid = veh.host_uuid AND h.parent_uuid = ?',
                $uuid
            ),
            []
        );

        return $this;
    }

    protected function prepareQuery()
    {
        $maxDays = 400;
        $query = $this->db->select()->from(['veh' => 'vm_event_history'], [
            // TODO: / 86400 + offset
            'day' => 'DATE(FROM_UNIXTIME(veh.ts_event_ms / 1000))',
            'cnt' => 'COUNT(*)'
        ])->where('veh.ts_event_ms > ?', time() * 1000 - 86400 * $maxDays * 1000)->group('day');

        if ($this->eventType !== null && $this->eventType !== '') {
            $query->where(
                'veh.event_type = ?',
                $this->eventType
            );
        }

        return $query;
    }

    public function getEvents()
    {
        return $this->db->fetchPairs($this->getQuery());
    }
}
