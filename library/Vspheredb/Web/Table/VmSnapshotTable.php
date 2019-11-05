<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;

class VmSnapshotTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => ['common-table'],
        'data-base-target' => '_next',
    ];

    /** @var VirtualMachine */
    protected $vm;

    public static function create(VirtualMachine $vm)
    {
        $tbl = new static($vm->getConnection());
        return $tbl->setVm($vm);
    }

    protected function setVm(VirtualMachine $vm)
    {
        $this->vm = $vm;

        return $this;
    }

    public function renderRow($row)
    {
        $this->renderDayIfNew($row->ts_create / 1000);

        return static::row([
            empty($row->description)
                ? $row->name
                : sprintf('%s: %s', $row->name, $row->description),
            DateFormatter::formatTime($row->ts_create / 1000)
        ]);
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            'vm_snapshot'
        )->order('ts_create DESC');

        if ($this->vm) {
            $query->where('vm_uuid = ?', $this->vm->get('uuid'));
        }

        return $query;
    }
}
