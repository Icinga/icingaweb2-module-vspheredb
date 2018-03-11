<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Html\Icon;
use dipl\Html\Link;
use Icinga\Module\Vspheredb\Web\Widget\DelayedPerfdataRenderer;
use Icinga\Module\Vspheredb\Web\Widget\PowerStateRenderer;

class VmsTable extends ObjectsTable
{
    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable', 'table-vms'],
        'data-base-target' => '_next',
    ];

    public function filterHost($uuid)
    {
        $this->getQuery()->where('vc.runtime_host_uuid = ?', $uuid);

        return $this;
    }

    public function prepareQuery()
    {
        $columns = $this->getRequiredDbColumns();
        $wantsHosts = false;
        foreach ($columns as $column) {
            if (substr($column, 0, 2) === 'h.') {
                $wantsHosts = true;
                break;
            }
        }

        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $columns
        )->join(
            ['vc' => 'virtual_machine'],
            'o.uuid = vc.uuid',
            []
        )->limit(14);

        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        if ($wantsHosts) {
            $query->joinLeft(
                ['h' => 'host_system'],
                'vc.runtime_host_uuid = h.uuid',
                []
            );
        }

        return $query;
    }

    protected function initialize()
    {
        $perf = new DelayedPerfdataRenderer($this->db());
        $powerStateRenderer = new PowerStateRenderer();
        $this->addAvailableColumns([
            $this->createColumn('overall_status', $this->translate('Status'), 'o.overall_status')
                ->setRenderer(function ($row) {
                    return Icon::create('ok', [
                        'title' => $this->getStatusDescription($row->overall_status),
                        'class' => [ 'state', $row->overall_status ]
                    ]);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn('runtime_power_state', $this->translate('Power'), 'vc.runtime_power_state')
                ->setRenderer($powerStateRenderer),
            $this->createColumn('object_name', 'Name', [
                'object_name' => 'o.object_name',
                'uuid'        => 'o.uuid',
            ])->setRenderer(function ($row) {
                return Link::create(
                    $row->object_name,
                    'vspheredb/vm',
                    ['uuid' => bin2hex($row->uuid)]
                );
            }),
            $this->createColumn('host_name', 'Host', [
                'host_name' => 'h.host_name',
            ]),
            $perf->getDiskColumn()->setDefaultSortDirection('DESC'),
            $perf->getNetColumn()->setDefaultSortDirection('DESC'),
            $perf->getCurrentNetColumn()->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_numcpu', 'CPUs', 'vc.hardware_numcpu')
                ->setDefaultSortDirection('DESC'),
            $this->createColumn('hardware_memorymb', 'Memory', 'vc.hardware_memorymb')
                ->setRenderer(function ($row) {
                    return $this->formatMb($row->hardware_memorymb);
                })->setDefaultSortDirection('DESC')
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'overall_status',
            'runtime_power_state',
            'object_name',
            'hardware_numcpu',
            'hardware_memorymb'
        ];
    }

    protected function getStatusDescription($status)
    {
        $descriptions = [
            'gray'   => $this->translate('Gray - status is unknown'),
            'green'  => $this->translate('Green - everything is fine'),
            'yellow' => $this->translate('Yellow - there are warnings'),
            'red'    => $this->translate('Red - there is a problem'),
        ];

        return $descriptions[$status];
    }
}
