<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Html\Link;
use Icinga\Module\Vspheredb\Web\Table\SimpleColumn;
use Icinga\Module\Vspheredb\Web\Widget\DelayedPerfdataRenderer;

// Other filter ideas:
// Problems:
// $query->where('overall_status IN (?)', ['yellow', 'red']);

// No Guest utils:
//$query->where('guest_tools_running_status != ?', 'guestToolsRunning')
//    ->where('runtime_power_state = ?', 'poweredOn')
//    ->where('guest_state = ?', 'running')

class VmsTable extends ObjectsTable
{
    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable', 'table-vms'],
        'data-base-target' => '_next',
    ];

    public function getColumnsToBeRendered()
    {
        return $this->getChosenTitles();
    }

    public function filterHost($uuid)
    {
        $this->getQuery()->where('vc.runtime_host_uuid = ?', $uuid);

        return $this;
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            [
                'uuid'                => 'o.uuid',
                'overall_status'      => 'o.overall_status',
                'runtime_power_state' => 'vc.runtime_power_state',
            ] + $this->getRequiredDbColumns()
        )->join(
            ['vc' => 'virtual_machine'],
            'o.uuid = vc.uuid',
            []
        )->order('object_name ASC')->limit(14);

        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        return $query;
    }

    protected function initialize()
    {
        $perf = new DelayedPerfdataRenderer($this->db());
        $this->addAvailableColumns([
            (new SimpleColumn('object_name', 'Name', 'o.object_name'))
                // TODO: require also uuid!
                ->setRenderer(function ($row) {
                    return Link::create(
                        $row->object_name,
                        'vspheredb/vm',
                        ['uuid' => bin2hex($row->uuid)]
                    );
                }),
            $perf->getDiskColumn(),
            $perf->getNetColumn(),
            new SimpleColumn('hardware_numcpu', 'Memory', 'vc.hardware_numcpu'),
            (new SimpleColumn('hardware_memorymb', 'CPUs', 'vc.hardware_memorymb'))
                ->setRenderer(function ($row) {
                    return $this->formatMb($row->hardware_memorymb);
                })
        ]);

        $this->chooseColumns([
            'object_name',
            'disk_io',
            'network_io',
            'hardware_numcpu',
            'hardware_memorymb'
        ]);
    }

    public function renderRow($row)
    {
        return parent::renderRow($row)->addAttributes(['class' => [
            $row->runtime_power_state,
            $row->overall_status
        ]]);
    }
}
