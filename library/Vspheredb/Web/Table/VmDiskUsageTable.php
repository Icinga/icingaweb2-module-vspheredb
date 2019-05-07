<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Img;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\SimpleUsageBar;
use Icinga\Util\Format;
use ipl\Html\Html;

class VmDiskUsageTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => ['vm-disk-usage-table', 'common-table', 'table-row-selectable'],
        'data-base-target' => '_next',
    ];

    protected $totalSize = 0;

    protected $totalFree = 0;

    /** @var VirtualMachine */
    protected $vm;

    /** @var string */
    protected $uuid;

    private $root;

    private $withHistory = false;

    public static function create(VirtualMachine $vm)
    {
        $tbl = new static($vm->getConnection());
        return $tbl->setVm($vm);
    }

    protected function setVm(VirtualMachine $vm)
    {
        $this->vm = $vm;
        $this->uuid = $vm->get('uuid');

        return $this;
    }

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Disk'),
            $this->translate('Size'),
            $this->translate('Free space'),
            $this->translate('Usage'),
        ];
    }

    /**
     * @param $row
     * @return \ipl\Html\HtmlElement
     * @throws \Icinga\Exception\NotFoundError
     */
    public function renderRow($row)
    {
        $caption = $row->disk_path;

        if ($caption === '/') {
            $this->root = $row;
        }

        $free = Format::bytes($row->free_space, Format::STANDARD_IEC)
            . sprintf(' (%0.3f%%)', ($row->free_space / $row->capacity) * 100);

        $tr = $this::tr([
            // TODO: move to CSS
            $this::td($caption),
            $this::td(Format::bytes($row->capacity, Format::STANDARD_IEC), ['style' => 'white-space: pre;']),
            $this::td($free, ['style' => 'width: 25%;']),
            $this::td($this->makeDisk($row), ['style' => 'width: 25%;'])
        ]);

        $this->totalSize += $row->capacity;
        $this->totalFree += $row->free_space;

        if ($this->withHistory) {
            $ciName = str_replace(' ', '_', $this->vm->object()->get('object_name'));
            $path = str_replace('/', '_', $row->disk_path);
            $path = str_replace(' ', '_', $path);
            $ci = $ciName . ':' . $path;
            $now = time();
            $end = floor($now / 60) * 60;
            $start = $end - 3600 * 4;
            $start = $end - 3600 * 24 * 14;
            $end = $start + 3600 * 24 * 4;
            $this->body()->add($tr);

            $tr = static::tr(static::td(
                Img::create('rrd/img', [
                    'file'     => $ci . '.rrd',
                    'rnd'      => time(),
                    'height'   => 120,
                    'width'    => 480,
                    'start'    => $start,
                    'end'      => $end,
                    'template' => 'vm_disk',
                ]), [
                    'colspan' => 4,
                    'style' => 'height: auto'
                ]
            ));

        }

        return $tr;
    }

    protected function fetchRows()
    {
        parent::fetchRows();

        $free = Format::bytes($this->totalFree, Format::STANDARD_IEC)
            . sprintf(' (%0.3f%%)', ($this->totalFree / $this->totalSize) * 100);
        $this->footer()->add($this::tr([
            $this::th(Html::tag('strong', null, $this->translate('Total'))),
            $this::th(Format::bytes($this->totalSize, Format::STANDARD_IEC), ['style' => 'white-space: pre;']),
            $this::th($free, ['style' => 'width: 25%;']),
            $this::th($this->makeDisk((object) [
                'disk_path' => $this->translate('Total'),
                'capacity'  => $this->totalSize,
                'free_space' => $this->totalFree
            ]), ['style' => 'width: 25%;'])
        ]));
    }

    public function generateFooter()
    {
        return Html::tag('tfoot');
    }

    protected function makeDisk($disk)
    {
        $used = $disk->capacity - $disk->free_space;

        return new SimpleUsageBar($used, $disk->capacity, $disk->disk_path);
    }

    public function prepareQuery()
    {
        return $this->db()->select()->from(
            'vm_disk_usage',
            ['disk_path', 'capacity', 'free_space']
        )->where(
            'vm_uuid = ?',
            $this->uuid
        )->order('disk_path');
    }
}
