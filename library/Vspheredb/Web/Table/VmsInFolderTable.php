<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Html\Link;
use dipl\Web\Table\ZfQueryBasedTable;

class VmsInFolderTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'object_name',
    ];

    protected $parentIds;

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Name'),
            $this->translate('CPUs'),
            $this->translate('Memory'),
        ];
    }

    public function filterParentIds(array $ids)
    {
        $this->parentIds = $ids;

        return $this;
    }

    public function renderRow($row)
    {
        $caption = Link::create(
            $row->object_name,
            'vspheredb/vm',
            ['id' => $row->id]
        );

        $tr = $this::row([$caption, $row->hardware_numcpu, $row->hardware_memorymb]);
        $tr->attributes()->add('class', [$row->runtime_power_state, $row->overall_status]);

        return $tr;
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            [
                'id'                => 'o.id',
                'object_name'       => 'o.object_name',
                'overall_status'    => 'o.overall_status',
                'annotation'        => 'vc.annotation',
                'hardware_memorymb' => 'vc.hardware_memorymb',
                'hardware_numcpu'   => 'vc.hardware_numcpu',
                'runtime_power_state' => 'vc.runtime_power_state',
            ]
        )->join(
            ['vc' => 'virtual_machine'],
            'o.id = vc.id',
            []
        )->order('object_name ASC');

        if ($this->parentIds) {
            $query->where('o.parent_id IN (?)', $this->parentIds);
        }

        // $query->where('overall_status IN (?)', ['yellow', 'red']);

        return $query;
    }
}
