<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use Icinga\Module\Vspheredb\DbObject\HostSystem;
use ipl\Html\Link;
use ipl\Web\Table\ZfQueryBasedTable;

class VmsOnHostTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'object_name',
    ];

    /** @var HostSystem */
    protected $host;

    public static function create(HostSystem $host)
    {
        $table = new static($host->getConnection());
        $table->host = $host;
        return $table;
    }

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Name'),
            $this->translate('CPUs'),
            $this->translate('Memory'),
        ];
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
        )->where(
            'vc.runtime_host_id = ?',
            $this->host->get('id')
        )->order('object_name ASC');

        return $query;
    }
}
