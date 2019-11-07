<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Date\DateFormatter;

class AlarmHistoryTable extends ZfQueryBasedTable
{
    use UuidLinkHelper;

    protected $entityUuid;

    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    public function filterEntityUuid($uuid)
    {
        $this->entityUuid = $uuid;

        return $this;
    }

    public function renderRow($row)
    {
        $this->renderDayIfNew($row->ts_event_ms / 1000);
        $content = [
            DateFormatter::formatTime($row->ts_event_ms / 1000),
        ];

        if ($this->entityUuid === null) {
            $this->linkToUuid($row->entity_uuid);
        }
        $content[] = $row->full_message;

        $tr = $this::row($content);

        return $tr;
    }
    protected function timeSince($ms)
    {
        return DateFormatter::timeAgo($ms);
    }

    protected function prepareQuery()
    {
        $query = $this->db()->select()->from([
            'ah' => 'alarm_history'
        ])->order('ts_event_ms DESC');

        if ($this->entityUuid === null) {
            $query->join(
                ['o' => 'object'],
                'o.uuid = ah.entity_uuid',
                []
            );
        } else {
            $query->where('ah.entity_uuid = ?', $this->entityUuid);
        }

        return $query;
    }
}
