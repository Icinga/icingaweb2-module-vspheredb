<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\ZfDb\Select;
use Zend_Db_Select;

class ResourcePoolsTable extends ObjectsTable
{
    protected ?string $baseUrl = 'vspheredb/resourcepool';

    protected function initialize(): void
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createObjectNameColumn(),
            $this->createColumn('cnt_vms', $this->translate('VMs'), 'COUNT(*)')
                ->setDefaultSortDirection('DESC')
        ]);
    }

    public function getDefaultColumnNames(): array
    {
        return [
            'overall_status',
            'object_name',
            'cnt_vms'
        ];
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->where('object_type = ?', 'ResourcePool');

        if ($this->hasColumn('cnt_vms')) {
            $query->joinLeft(['vm' => 'virtual_machine'], 'vm.resource_pool_uuid = o.uuid', [])->group('o.uuid');
        }

        return $query;
    }
}
