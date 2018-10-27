<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Web\Table\ZfQueryBasedTable;

class PerformanceCounterTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'counter_key',
        'name',
        'group_name',
        'unit_name',
        'label',
        'summary',
        'stats_type',
        'rollup_type',
        // TODO: disabled, Director breaks this right now for security reasons
        // "(c.group_name || '.' || c.name)",
    ];

    protected $parentIds;

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Key'),
            $this->translate('Name'),
            $this->translate('Unit'),
            $this->translate('Stats'),
            $this->translate('Rollup'),
            $this->translate('ID'),
        ];
    }

    public function renderRow($row)
    {
        $tr = $this::row([
            sprintf(
                '%s.%s',
                $row->group_name,
                $row->name
            ),
            sprintf(
                '%s: %s',
                $row->label,
                $row->summary
            ),
            $row->unit_name,
            $row->stats_type,
            $row->rollup_type,
            $row->counter_key,
        ]);

        return $tr;
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['c' => 'performance_counter']
        )->order('group_name')->order('name')->order('unit_name');

        return $query;
    }
}
