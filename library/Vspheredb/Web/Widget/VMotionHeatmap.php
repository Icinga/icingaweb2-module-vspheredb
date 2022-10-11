<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Db;
use Zend_Db_Select as ZfSelect;

class VMotionHeatmap
{
    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    protected $query;

    protected $eventType;

    public function __construct(Db $connection)
    {
        $this->db = $connection->getDbAdapter();
    }

    public function getEvents(): array
    {
        return $this->db->fetchPairs($this->getQuery());
    }

    public function filterEventType($type): self
    {
        $this->eventType = $type;

        return $this;
    }

    public function filterParent($uuid): self
    {
        $this->getQuery()->join(['h' => 'object'], $this->db->quoteInto(
            'h.uuid = veh.host_uuid AND h.parent_uuid = ?',
            $uuid
        ), []);

        return $this;
    }

    protected function prepareQuery(): ZfSelect
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

    protected function getQuery(): ZfSelect
    {
        if ($this->query === null) {
            $this->query = $this->prepareQuery();
        }

        return $this->query;
    }
}
