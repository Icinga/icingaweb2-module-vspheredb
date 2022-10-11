<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Db;
use Zend_Db_Select as ZfSelect;

class AlarmHeatmap
{
    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    protected $query;

    public function __construct(Db $connection)
    {
        $this->db = $connection->getDbAdapter();
    }

    public function getEvents(): array
    {
        return $this->db->fetchPairs($this->getQuery());
    }

    protected function prepareQuery(): ZfSelect
    {
        $maxDays = 400;
        return $this->db->select()->from('alarm_history', [
            // TODO: / 86400 + offset
            'day' => 'DATE(FROM_UNIXTIME(ts_event_ms / 1000))',
            'cnt' => 'COUNT(*)'
        ])->where('ts_event_ms > ?', time() * 1000 - 86400 * $maxDays * 1000)->group('day');
    }

    protected function getQuery(): ZfSelect
    {
        if ($this->query === null) {
            $this->query = $this->prepareQuery();
        }

        return $this->query;
    }
}
