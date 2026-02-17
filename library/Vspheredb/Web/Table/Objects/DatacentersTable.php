<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\ZfDb\Select;
use Zend_Db_Select;

class DatacentersTable extends ObjectsTable
{
    protected ?string $baseUrl = 'vspheredb/vms?showDescendants';

    protected function initialize(): void
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createObjectNameColumn()
        ]);
    }

    public function getDefaultColumnNames(): array
    {
        return [
            'overall_status',
            'object_name'
        ];
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        return $this->db()->select()
            ->from(['o' => 'object'], $this->getRequiredDbColumns())
            ->where('object_type = ?', 'Datacenter');
    }
}
