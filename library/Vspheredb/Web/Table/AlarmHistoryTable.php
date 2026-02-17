<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\ZfDb\Select;
use Icinga\Date\DateFormatter;
use ipl\Html\HtmlElement;
use Zend_Db_Select;

class AlarmHistoryTable extends ZfQueryBasedTable
{
    use UuidLinkHelper;

    protected ?string $entityUuid = null;

    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    public function filterEntityUuid($uuid): static
    {
        $this->entityUuid = $uuid;

        return $this;
    }

    public function renderRow($row): HtmlElement
    {
        $this->renderDayIfNew($row->ts_event_ms / 1000);
        $content = [DateFormatter::formatTime($row->ts_event_ms / 1000)];

        if ($this->entityUuid === null) {
            $this->linkToUuid($row->entity_uuid);
        }
        $content[] = $row->full_message;

        return $this::row($content);
    }

    protected function timeSince(int $ms): ?string
    {
        return DateFormatter::timeAgo($ms);
    }

    protected function prepareQuery(): Select|Zend_Db_Select
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
