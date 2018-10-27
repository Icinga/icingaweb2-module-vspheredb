<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Vspheredb\DbObject\DistributedVirtualPortgroup;

class NetworkAdaptersTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/vm';

    /** @var DistributedVirtualPortgroup|null */
    protected $portGroup;

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->join(
            ['vm' => 'virtual_machine'],
            'o.uuid = vm.uuid',
            []
        )->join(
            ['vh' => 'vm_hardware'],
            'vh.vm_uuid = vm.uuid',
            []
        )->join(
            ['vna' => 'vm_network_adapter'],
            'vna.vm_uuid = vh.vm_uuid AND vna.hardware_key = vh.hardware_key',
            []
        );

        if ($this->portGroup) {
            $query->where(
                'portgroup_uuid = ?',
                $this->portGroup->get('uuid')
            );
        }

        return $query;
    }

    public function filterPortGroup(DistributedVirtualPortgroup $portGroup)
    {
        $this->portGroup = $portGroup;

        return $this;
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createColumn('port_key', $this->translate('Port'), 'vna.port_key'),
            $this->createObjectNameColumn(),
            $this->createColumn('label', $this->translate('Interface'), [
                'vh.label',
            ]),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'port_key',
            'overall_status',
            'object_name',
            'label'
        ];
    }
}
