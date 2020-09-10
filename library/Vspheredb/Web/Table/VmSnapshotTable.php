<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;

class VmSnapshotTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => ['common-table', 'day-time-table'],
        'data-base-target' => '_next',
    ];

    /** @var VirtualMachine */
    protected $vm;

    public function __construct(VirtualMachine $vm)
    {
        parent::__construct($vm->getConnection());
        $this->setVm($vm);
    }

    protected function setVm(VirtualMachine $vm)
    {
        $this->vm = $vm;

        return $this;
    }

    protected function assemble()
    {
        parent::assemble();
        if (count($this) === 0) {
            $this->prepend(
                Html::tag('p', null, $this->translate('No snapshots have been created for this VM'))
            );
        }
        $this->prepend(new SubTitle($this->translate('Snapshots'), 'history'));
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
