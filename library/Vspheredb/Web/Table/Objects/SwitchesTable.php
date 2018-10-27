<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

class SwitchesTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/switch';

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->join(
            ['vds' => 'distributed_virtual_switch'],
            'o.uuid = vds.uuid',
            []
        );

        return $query;
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createObjectNameColumn(),
            $this->createColumn('num_hosts', $this->translate('Hosts'), 'vds.num_hosts'),
            $this->createColumn('num_ports', $this->translate('Ports'), 'vds.num_ports'),
            $this->createColumn('max_ports', $this->translate('Max Ports'), 'vds.max_ports'),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'overall_status',
            'object_name',
            'num_hosts',
            'num_ports'
        ];
    }
}
