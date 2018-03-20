<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

class ResourcePoolsTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/resourcepool';

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createObjectNameColumn(),
            $this->createMorefColumn(),
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
        )->where('object_type = ?', 'ResourcePool');

        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        return $query;
    }
}
