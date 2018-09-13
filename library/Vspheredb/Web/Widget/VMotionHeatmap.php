<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\DbObject\VCenter;

class VMotionHeatmap extends EventHeatmapCalendars
{
    protected $db;

    protected $query;

    protected $eventType;

    public function __construct(VCenter $vCenter, $baseUrl)
    {
        $this->setBaseUrl($baseUrl);
        $this->db = $vCenter->getDb();
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
        $query = $this->db->select()->from(['veh' => 'vm_event_history'], [
            // TODO: / 86400 + offset
            'day' => 'DATE(FROM_UNIXTIME(veh.ts_event_ms / 1000))',
            'cnt' => 'COUNT(*)'
        ])->group('day');

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
