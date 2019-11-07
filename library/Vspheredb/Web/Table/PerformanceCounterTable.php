<?php

namespace Icinga\Module\Vspheredb\Web\Table;

class PerformanceCounterTable extends BaseTable
{
    /** string */
    protected $vCenterUuid;

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

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createColumn('key',$this->translate('Key'),  [
                'group_name',
                'name'
            ])->setRenderer(function ($row) {
                return sprintf(
                    '%s.%s',
                    $row->group_name,
                    $row->name
                );
            }),
            $this->createColumn('name', $this->translate('Name'), [
                'group_name',
                'name'
            ])->setRenderer(function ($row) {
                return sprintf(
                    '%s.%s',
                    $row->label,
                    $row->summary
                );
            }),
            $this->createColumn('unit_name', $this->translate('Unit')),
            $this->createColumn('stats_type', $this->translate('Stats')),
            $this->createColumn('rollup_type', $this->translate('Rollup')),
            $this->createColumn('counter_key', $this->translate('ID')),
        ]);
    }

    public function filterVCenterUuid($hexUuid)
    {
        $this->vCenterUuid = \hex2bin($hexUuid);

        return $this;
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['c' => 'performance_counter']
        );
        // ->order('group_name')->order('name')->order('unit_name');

        if ($this->vCenterUuid !== null) {
            $query->where('vcenter_uuid = ?', $this->vCenterUuid);
        }

        return $query;
    }
}
