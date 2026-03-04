<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\ZfDb\Select;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PerformanceData\IcingaRrd\RrdImg;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use Icinga\Util\Format;
use ipl\Html\FormattedString;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use Zend_Db_Select;

class VmDisksTable extends ZfQueryBasedTable
{
    protected $searchColumns = ['object_name'];

    protected $parentIds;

    /** @var VirtualMachine */
    protected VirtualMachine $vm;

    /** @var ?string */
    protected ?string $uuid = null;

    /** @var ?string */
    protected ?string $moref = null;

    /** @var OverallStatusRenderer */
    protected OverallStatusRenderer $renderStatus;

    protected bool $withPerfImages = false;

    public function __construct(VirtualMachine $vm)
    {
        parent::__construct($vm->getConnection());
        $this->prepend(new SubTitle($this->translate('Disks'), 'database'));
        $this->renderStatus = new OverallStatusRenderer();
        $this->setVm($vm);
    }

    protected function setVm(VirtualMachine $vm): static
    {
        $this->vm = $vm;
        $this->uuid = $vm->get('uuid');
        $this->moref = $vm->object()->get('moref');

        return $this;
    }

    public function renderRow($row): HtmlElement
    {
        $device = sprintf(
            '%s%d:%d',
            strtolower(preg_replace('/\s.+$/', '', $row->controller_label)),
            $row->hardware_bus_number,
            $row->hardware_unit_nmber
        );
        // $this->add($this::row([
        //     new GrafanaVmPanel($this->vm->object(), [10, 61, 60], 'All', $row->hardware_label)
        // ]));

        if ($this->withPerfImages) {
            return $this::tr([
                $this::td([
                    Html::tag('strong', $row->hardware_label),
                    Html::tag('br'),
                    $device,
                    Html::tag('br'),
                    Format::bytes($row->capacity)
                ], ['class' => 'vm-disks-with-perf-image']),
                $this->prepareImgColumn($device)
            ]);
        }

        return $this->row([$this->formatSimple($row, $device)]);
    }

    protected function formatSimple(object $row, string $device): FormattedString
    {
        return Html::sprintf(
            '%s (%s): %s',
            Html::tag('strong', $row->hardware_label),
            $device,
            Format::bytes($row->capacity)
        );
    }

    protected function prepareImgColumn($device): ?HtmlElement
    {
        if ($this->withPerfImages) {
            return $this::td([
                RrdImg::vmDiskSeeks($this->moref, $device),
                RrdImg::vmDiskReadWrites($this->moref, $device),
                RrdImg::vmDiskTotalLatency($this->moref, $device)
            ]);
        }

        return null;
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        return $this->db()->select()
            ->from(['vmd' => 'vm_disk'], [
                'controller_label'    => 'vmhc.label',
                'hardware_label'      => 'vmhw.label',
                'hardware_key'        => 'vmhw.hardware_key',
                'hardware_bus_number' => 'vmhc.bus_number',
                'hardware_unit_nmber' => 'vmhw.unit_number',
                'capacity'            => 'vmd.capacity'
            ])
            ->join(['vmhw' => 'vm_hardware'], 'vmd.vm_uuid = vmhw.vm_uuid AND vmd.hardware_key = vmhw.hardware_key', [])
            ->join(
                ['vmhc' => 'vm_hardware'],
                'vmhw.vm_uuid = vmhc.vm_uuid AND vmhw.controller_key = vmhc.hardware_key',
                []
            )
            ->where('vmd.vm_uuid = ?', $this->vm->get('uuid'))
            ->order('hardware_label');
    }
}
