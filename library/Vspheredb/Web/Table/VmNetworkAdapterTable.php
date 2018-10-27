<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Util\Format;
use dipl\Html\Link;
use dipl\Web\Table\ZfQueryBasedTable;

class VmNetworkAdapterTable extends ZfQueryBasedTable
{
    /** @var VirtualMachine */
    protected $vm;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
        parent::__construct($vm->getConnection());
    }

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Interface'),
            $this->translate('MAC Address'),
            $this->translate('Port'),
        ];
    }

    public function renderRow($row)
    {
        if ($row->portgroup_uuid === null) {
            $portGroup = '-';
        } else {
            $portGroup = [Link::create(
                $row->portgroup_name,
                'vspheredb/portgroup',
                ['uuid' => bin2hex($row->portgroup_uuid)],
                ['data-base-target' => '_next']
            ), ': ' . $row->port_key];
        }
        return $this::row([
            $row->label,
            $row->mac_address,
            $portGroup,
        ]);
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['vna' => 'vm_network_adapter'],
            [
                'vh.label',
                'vna.hardware_key',
                'vna.port_key',
                'vna.mac_address',
                'vna.address_type',
                'vna.portgroup_uuid',
                'portgroup_name' => 'pgo.object_name',
            ]
        )->join(
            ['vh' => 'vm_hardware'],
            'vh.vm_uuid = vna.vm_uuid AND vh.hardware_key = vna.hardware_key',
            []
        )->joinLeft(
            ['pgo' => 'object'],
            'pgo.uuid = vna.portgroup_uuid',
            []
        )->where('vna.vm_uuid = ?', $this->vm->get('uuid'))->order('vh.label ASC');

        return $query;
    }
}
