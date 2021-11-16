<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Db;

class AlarmHeatmap extends EventHeatmapCalendars
{
    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    protected $query;

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

    protected function prepareQuery()
    {
        $maxDays = 400;
        return $this->db->select()->from('alarm_history', [
            // TODO: / 86400 + offset
            'day' => 'DATE(FROM_UNIXTIME(ts_event_ms / 1000))',
            'cnt' => 'COUNT(*)'
        ])->where('ts_event_ms > ?', time() * 1000 - 86400 * $maxDays * 1000)->group('day');
    }

    public function getEvents()
    {
        return $this->db->fetchPairs($this->getQuery());
    }
}
