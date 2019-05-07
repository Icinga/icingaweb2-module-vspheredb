<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PerformanceData\IcingaRrd\RrdImg;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Icinga\Util\Format;
use ipl\Html\Html;

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

    protected $withPerfImages = false;

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

    public function renderRow($row)
    {
        $device = sprintf(
            '%s%d:%d',
            strtolower(preg_replace('/\s.+$/', '', $row->controller_label)),
            $row->hardware_bus_number,
            $row->hardware_unit_nmber
        );

        if ($this->withPerfImages) {
            return $this::tr([
                $this::td([
                    Html::tag('strong', $row->hardware_label),
                    Html::tag('br'),
                    $device,
                    Html::tag('br'),
                    Format::bytes($row->capacity),
                ], ['style' => 'vertical-align: top; min-width: 15em;']),
                $this->prepareImgColumn($device)
            ]);
        } else {
            return $this->row([
                $this->formatSimple($row, $device)
            ]);
        }
    }

    protected function formatSimple($row, $device)
    {
        return Html::sprintf(
            '%s (%s): %s',
            Html::tag('strong', $row->hardware_label),
            $device,
            Format::bytes($row->capacity)
        );
    }

    protected function prepareImgColumn($device)
    {
        if ($this->withPerfImages) {
            return $this::td([
                RrdImg::vmDiskSeeks($this->moref, $device),
                RrdImg::vmDiskReadWrites($this->moref, $device),
                RrdImg::vmDiskTotalLatency($this->moref, $device),
            ]);
        } else {
            return null;
        }
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
