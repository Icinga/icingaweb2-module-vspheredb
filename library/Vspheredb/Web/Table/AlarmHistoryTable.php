<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Web\Table\ZfQueryBasedTable;
use Icinga\Date\DateFormatter;

class AlarmHistoryTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    protected $requiredUuids = [];

    protected $fetchedUuids;

    public function renderRow($row)
    {
        $this->renderDayIfNew($row->ts_event_ms / 1000);
        $content = [
            $row->full_message
        ];

        $tr = $this::row([
            $content,
            DateFormatter::formatTime($row->ts_event_ms / 1000)
        ]);

        return $tr;
    }

    protected function getUuidName($uuid)
    {
        if ($uuid === null) {
            return '[NULL]';
        }

        if ($this->fetchedUuids === null) {
            $this->fetchUuidNames();
        }

        if (array_key_exists($uuid, $this->fetchedUuids)) {
            return $this->fetchedUuids[$uuid];
        } else {
            return '[UNKNOWN]';
        }
    }

    protected function fetchUuidNames()
    {
        $db = $this->db();
        if (empty($this->requiredUuids)) {
            $this->fetchedUuids = [];

            return;
        }

        $this->fetchedUuids = $db->fetchPairs(
            $db->select()
                ->from('object', ['uuid', 'object_name'])
                ->where('uuid IN (?)', array_values($this->requiredUuids))
        );
    }

    protected function timeSince($ms)
    {
        return DateFormatter::timeAgo($ms);
    }

    protected function prepareQuery()
    {
        $query = $this->db()->select()->from([
            'ah' => 'alarm_history'
        ])->join(
            ['o' => 'object'],
            'o.uuid = ah.entity_uuid',
            []
        )->order('ts_event_ms DESC');

        return $query;
    }
}
