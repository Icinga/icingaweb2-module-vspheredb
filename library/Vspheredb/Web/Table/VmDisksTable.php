<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Html\Html;
use dipl\Html\Img;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Icinga\Util\Format;
use dipl\Web\Table\ZfQueryBasedTable;

class VmDisksTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'object_name',
    ];

    protected $parentIds;

    /** @var VirtualMachine */
    protected $vm;

    /** @var string */
    protected $uuid;

    /** @var string */
    protected $moref;

    /** @var OverallStatusRenderer */
    protected $renderStatus;

    public static function create(VirtualMachine $vm)
    {
        $tbl = new static($vm->getConnection());
        $tbl->renderStatus = new OverallStatusRenderer();

        return $tbl->setVm($vm);
    }

    protected function setVm(VirtualMachine $vm)
    {
        $this->vm = $vm;
        $this->uuid = $vm->get('uuid');
        $this->moref = $vm->object()->get('moref');

        return $this;
    }

    public function getColumnsToBeRendered()
    {
        return [
            // TODO: no padding in th on our left!
            Html::tag('h2', [
                'class' => 'icon-database',
                'style' => 'margin: 0;'
            ], $this->translate('Disks')),
            ''
        ];
    }

    protected function prepareImg($device, $template)
    {
        $width = 300;
        $height = 140;
        $height = 60;
        $end = floor(time() / 300) * 300;
        $start = $end - 86400;
        $start = $end - 14400;
        $params = [
            'file'     => sprintf('%s/disk%s.rrd', $this->moref, $device),
            'height'   => $height,
            'width'    => $width,
            'rnd'      => floor(time() / 20),
            'format'   => 'png',
            'start'    => $start,
            'end'      => $end,
            'onlyGraph' => 1,
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
            'style' => 'display: inline-block; margin-left: 1em;'
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
        $device = sprintf(
            '%s%d:%d',
            strtolower(preg_replace('/\s.+$/', '', $row->controller_label)),
            $row->hardware_bus_number,
            $row->hardware_unit_nmber
        );
        return $this::tr([
            $this::td([
                Html::tag('strong', $row->hardware_label),
                Html::tag('br'),
                $device,
                Html::tag('br'),
                Format::bytes($row->capacity),
            ], ['style' => 'vertical-align: top; min-width: 15em;']),
            $this::td([
                $this->wrapImage(Html::sprintf(
                    $this->translate('Disk Seeks: %s small / %s medium / %s large'),
                    $this->colorLegend('#57985B'),
                    $this->colorLegend('#FFED58'),
                    $this->colorLegend('#FFBF58')
                ), $device, 'vSphereDB-vmDiskSeeks'),
                $this->wrapImage(Html::sprintf(
                    $this->translate('Average Number %s Reads / %s Writes'),
                    $this->colorLegend('#57985B'),
                    $this->colorLegend('#0095BF')
                ), $device, 'vSphereDB-vmDiskReadWrites'),
                $this->wrapImage(Html::sprintf(
                    $this->translate('Latency %s Read / %s Write'),
                    $this->colorLegend('#57985B'),
                    $this->colorLegend('#0095BF')
                ), $device, 'vSphereDB-vmDiskTotalLatency'),
            ])
        ]);
    }

    public function prepareQuery()
    {
        $uuid = $this->vm->get('uuid');
        $query = $this->db()->select()->from(['vmd' => 'vm_disk'], [
            'controller_label'    => 'vmhc.label',
            'hardware_label'      => 'vmhw.label',
            'hardware_key'        => 'vmhw.hardware_key',
            'hardware_bus_number' => 'vmhc.bus_number',
            'hardware_unit_nmber' => 'vmhw.unit_number',
            'capacity'            => 'vmd.capacity',
        ])
        ->join(['vmhw' => 'vm_hardware'], 'vmd.vm_uuid = vmhw.vm_uuid AND vmd.hardware_key = vmhw.hardware_key', [])
        ->join(['vmhc' => 'vm_hardware'], 'vmhw.vm_uuid = vmhc.vm_uuid AND vmhw.controller_key = vmhc.hardware_key', [])
        ->where('vmd.vm_uuid = ?', $uuid)
        ->order('hardware_label');

        return $query;
    }
}
