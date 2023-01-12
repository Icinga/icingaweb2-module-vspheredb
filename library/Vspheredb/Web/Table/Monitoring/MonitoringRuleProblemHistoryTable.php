<?php

namespace Icinga\Module\Vspheredb\Web\Table\Monitoring;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Data\Anonymizer;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Table\TableWithVCenterFilter;
use Icinga\Module\Vspheredb\Web\Table\UuidLinkHelper;
use Icinga\Module\Vspheredb\Web\Widget\CheckPluginHelper;
use ipl\Html\Html;

class MonitoringRuleProblemHistoryTable extends ZfQueryBasedTable implements TableWithVCenterFilter
{
    use UuidLinkHelper;

    protected $entityUuid;

    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable'],
        'data-base-target' => '_next',
    ];

    public function filterEntityUuid($uuid)
    {
        $this->entityUuid = $uuid;

        return $this;
    }

    public function renderRow($row)
    {
        $this->renderDayIfNew($row->ts_changed_ms / 1000);

        $formerState = sprintf($this->translate('( former state: [%s] )'), $row->former_state);
        if ($row->output === null) {
            $output = [
                CheckPluginHelper::colorizeOutput($this->translate('[OK] Check has no longer been executed')),
                ' ',
                CheckPluginHelper::colorizeOutput($formerState),
            ];
        } else {
            $lines = preg_split("/\r?\n/", $row->output);
            $lines[0] .= " $formerState";
            $output = CheckPluginHelper::colorizeOutput(implode("\n", $lines));
        }

        if ($this->entityUuid) {
            $cell[] = Html::tag('strong', $row->rule_name);
        } else {
            $cell[] = Html::sprintf(
                $this->translate("%s on %s"),
                Html::tag('strong', $row->rule_name),
                $this->linkToObject($row) // No link if entityUuid!!
            );
        }
        $cell[] = "\n";
        $cell[] = $output;

        $tr = $this::row([Html::tag('pre', [
            'class' => 'logOutput'
        ], $cell), DateFormatter::formatTime($row->ts_changed_ms / 1000)]);

        return $tr;
    }

    protected function linkToObject($row)
    {
        return Link::create(
            Anonymizer::anonymizeString($row->object_name),
            $this->getBaseUrl($row),
            Util::uuidParams($row->uuid)
        );
    }

    protected function getBaseUrl($row): ?string
    {
        switch ($row->object_type) {
            case 'HostSystem':
                return 'vspheredb/host';
            case 'VirtualMachine':
                return 'vspheredb/vm';
            case 'Datastore':
                return 'vspheredb/datastore';
            default:
                return null;
        }
    }

    protected function prepareQuery()
    {
        // uuid, current_state, former_state, rule_name, ts_changed_ms, output
        $query = $this->db()->select()->from([
            'ph' => 'monitoring_rule_problem_history'
        ], [
            'o.object_name',
            'o.object_type',
            'ph.uuid',
            'ph.current_state',
            'ph.former_state',
            'ph.rule_name',
            'ph.ts_changed_ms',
            'ph.output',
        ])->join(
            ['o' => 'object'],
            'o.uuid = ph.uuid',
            []
        )->order('ts_changed_ms DESC');

        if ($this->entityUuid !== null) {
            $query->where('ph.uuid = ?', $this->entityUuid);
        }

        return $query;
    }

    public function filterVCenter(VCenter $vCenter): self
    {
        return $this->filterVCenterUuids([$vCenter->getUuid()]);
    }

    public function filterVCenterUuids(array $uuids): self
    {
        if (empty($uuids)) {
            $this->getQuery()->where('1 = 0');
            return $this;
        }

        $db = $this->db();
        $query = $this->getQuery();
        if (count($uuids) === 1) {
            $query->where("o.vcenter_uuid = ?", DbUtil::quoteBinaryCompat(array_shift($uuids), $db));
        } else {
            $query->where("o.vcenter_uuid IN (?)", DbUtil::quoteBinaryCompat($uuids, $db));
        }

        return $this;
    }
}
