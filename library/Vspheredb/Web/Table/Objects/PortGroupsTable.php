<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Module\Director\Core\Json;
use Icinga\Module\Vspheredb\DbObject\DistributedVirtualSwitch;
use ipl\Html\Html;

class PortGroupsTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/portgroup';

    /** @var DistributedVirtualSwitch|null */
    protected $switch;

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->join(
            ['vdp' => 'distributed_virtual_portgroup'],
            'o.uuid = vdp.uuid',
            []
        );

        if ($this->switch) {
            $query->where(
                'distributed_virtual_switch_uuid = ?',
                $this->switch->get('uuid')
            );
        }

        return $query;
    }

    public function filterSwitch(DistributedVirtualSwitch $switch)
    {
        $this->switch = $switch;

        return $this;
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createOverallStatusColumn(),
            $this->createObjectNameColumn(),
            $this->createColumn('vlan', $this->translate('VLAN'), [
                'vdp.vlan',
                'vdp.vlan_ranges',
            ])->setRenderer(function ($row) {
                if ($row->vlan === null) {
                    if ($row->vlan_ranges === null) {
                        return '-';
                    } else {
                        $ranges = [];
                        foreach (Json::decode($row->vlan_ranges) as $range) {
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
                    }
                } else {
                    return $row->vlan;
                }
            }),
            $this->createColumn('num_ports', $this->translate('Ports'), 'vdp.num_ports'),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'overall_status',
            'object_name',
            'vlan',
            'num_ports'
        ];
    }
}
