<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\Json\JsonString;
use gipfl\ZfDb\Select;
use Icinga\Module\Vspheredb\DbObject\DistributedVirtualSwitch;
use ipl\Html\Html;
use Zend_Db_Select;

class PortGroupsTable extends ObjectsTable
{
    protected ?string $baseUrl = 'vspheredb/portgroup';

    /** @var ?DistributedVirtualSwitch */
    protected ?DistributedVirtualSwitch $switch = null;

    public function prepareQuery(): Select|Zend_Db_Select
    {
        $query = $this->db()->select()
            ->from(['o' => 'object'], $this->getRequiredDbColumns())
            ->join(['vdp' => 'distributed_virtual_portgroup'], 'o.uuid = vdp.uuid', []);

        if ($this->switch) {
            $query->where('distributed_virtual_switch_uuid = ?', $this->switch->get('uuid'));
        }

        return $query;
    }

    public function filterSwitch(DistributedVirtualSwitch $switch): static
    {
        $this->switch = $switch;

        return $this;
    }

    protected function initialize(): void
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createObjectNameColumn(),
            $this->createColumn('vlan', $this->translate('VLAN'), [
                'vdp.vlan',
                'vdp.vlan_ranges'
            ])
                ->setRenderer(function ($row) {
                    if ($row->vlan !== null) {
                        return $row->vlan;
                    }

                    if ($row->vlan_ranges === null) {
                        return '-';
                    }

                    $ranges = [];
                    foreach (JsonString::decode($row->vlan_ranges) as $range) {
                        if (! empty($ranges)) {
                            $ranges[] = Html::tag('br');
                        }
                        $ranges[] = sprintf(
                            '%s - %s',
                            $range->start,
                            $range->end
                        );
                    }

                    return $ranges;
                }),
            $this->createColumn('num_ports', $this->translate('Ports'), 'vdp.num_ports')
        ]);
    }

    public function getDefaultColumnNames(): array
    {
        return [
            'overall_status',
            'object_name',
            'vlan',
            'num_ports'
        ];
    }
}
