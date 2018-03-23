<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

class DatacentersTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/datacenter';

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createObjectNameColumn()

        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'overall_status',
            'object_name',
        ];
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->where('object_type = ?', 'DataCenter');

        return $query;
    }
}
