<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Html\Html;
use dipl\Html\Img;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use dipl\Html\Link;
use dipl\Web\Table\ZfQueryBasedTable;

class VmNetworkAdapterTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    /** @var VirtualMachine */
    protected $vm;

    /** @var string */
    protected $moref;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
        $this->moref = $this->vm->object()->get('moref');
        parent::__construct($vm->getConnection());
    }

    public function getColumnsToBeRendered()
    {
        return [
            // TODO: no padding in th on our left!
            Html::tag('h2', [
                'class' => 'icon-sitemap',
                'style' => 'margin: 0;'
            ], $this->translate('Network')),
            ''
        ];
    }

    protected function prepareImg($device, $template)
    {
        $width = 340 + 170;
        $height = 180;
        $start = strtotime('2019-01-31 08:50:00');
        $end = strtotime('2019-01-31 16:00:00');
        $params = [
            'file'     => sprintf('%s/iface%s.rrd', $this->moref, $device),
            'height'   => $height,
            'width'    => $width,
            'rnd'      => floor(time() / 20),
            'format'   => 'png',
            'start'    => $start,
            'end'      => $end,
        ];
        $attrs = [
            'height' => $height,
            'width'  => $width,
            //'align'  => 'right',
            'style' => 'float: right;'
            // 'style'  => 'border-bottom: 1px solid rgba(0, 0, 0, 0.3); border-left: 1px solid rgba(0, 0, 0, 0.3);'
        ];

        return Img::create('rrd/graph/img', $params + [
                'template' => $template,
            ], $attrs);
    }

    protected function wrapImage($title, $device, $template)
    {
        return Html::tag('div', [
                'style' => 'display: inline-block; margin-left: 2em;'
        ], [
            Html::tag('strong', [
                'style' => 'display: block; padding-left: 3em'
            ], $title),
            $this->prepareImg($device, $template),
        ]);
    }

    protected function colorLegend($color)
    {
        return Html::tag('div', [
            'style' => "    border: 1px solid rgba(0, 0, 0, 0.3); background-color: $color;"
                . ' width: 0.8em; height: 0.8em; margin: 0.1em; display: inline-block; vertical-align: middle;'
        ]);
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
            [
                Html::tag('strong', $row->label),
                Html::tag('br'),
                $this->translate('MAC Address') . ': ' . $row->mac_address,
                Html::tag('br'),
                $this->translate('Port'),
                ': ',
                $portGroup
            ], [
                $this->wrapImage(Html::sprintf(
                    $this->translate('Throughput (bits/s, %s RX / %s TX)'),
                    $this->colorLegend('#57985B'),
                    $this->colorLegend('#0095BF')
                ), $row->hardware_key, 'vSphereDB-vmIfTraffic'),
                $this->wrapImage(Html::sprintf(
                    $this->translate('Packets (%s / %s Unicast, %s BCast, %s MCast, %s Dropped)'),
                    $this->colorLegend('#57985B'),
                    $this->colorLegend('#0095BF'),
                    $this->colorLegend('#EE55FF'),
                    $this->colorLegend('#FF9933'),
                    $this->colorLegend('#FF5555')
                ), $row->hardware_key, 'vSphereDB-vmIfPackets'),
            ]
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
