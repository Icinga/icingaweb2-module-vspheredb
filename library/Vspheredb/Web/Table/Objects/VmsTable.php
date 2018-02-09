<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Html\DeferredText;
use dipl\Html\Link;
use Icinga\Module\Vspheredb\Web\Widget\CompactInOutSparkline;

// Other filter ideas:
// Problems:
// $query->where('overall_status IN (?)', ['yellow', 'red']);

// No Guest utils:
//$query->where('guest_tools_running_status != ?', 'guestToolsRunning')
//    ->where('runtime_power_state = ?', 'poweredOn')
//    ->where('guest_state = ?', 'running')

class VmsTable extends ObjectsTable
{
    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable', 'table-vms'],
        'data-base-target' => '_next',
    ];

    protected $requiredVms = [];

    protected $perf;

    protected $counters = [
        526 => 'net.bytesRx',
        527 => 'net.bytesRx',
        171 => 'virtualDisk.numberReadAveraged',
        172 => 'virtualDisk.numberWriteAveraged',
    ];

    public function getColumnsToBeRendered()
    {
        return array_merge(
            [$this->translate('Name')],
            $this->getPerfTitles(),
            [
                $this->translate('CPUs'),
                $this->translate('Memory'),
            ]
        );
    }

    protected function getPerfTitles()
    {
        return [
            $this->translate('5x5min Disk I/O'),
            $this->translate('Network I/O'),
        ];
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            [
                'uuid'                => 'o.uuid',
                'moref'               => 'o.moref',
                'object_name'         => 'o.object_name',
                'overall_status'      => 'o.overall_status',
                'annotation'          => 'vc.annotation',
                'hardware_memorymb'   => 'vc.hardware_memorymb',
                'hardware_numcpu'     => 'vc.hardware_numcpu',
                'runtime_power_state' => 'vc.runtime_power_state',
            ]
        )->join(
            ['vc' => 'virtual_machine'],
            'o.uuid = vc.uuid',
            []
        )->order('object_name ASC')->limit(14);

        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        return $query;
    }

    public function renderRow($row)
    {
        $this->requireVm($row->uuid);
        $caption = Link::create(
            $row->object_name,
            'vspheredb/vm',
            ['uuid' => bin2hex($row->uuid)]
        );

        $tr = $this::row(array_merge(
            [$caption],
            $this->createPerfColumns($row->uuid),
            [
                $row->hardware_numcpu,
                $row->hardware_memorymb
            ]
        ));
        $tr->attributes()->add('class', [$row->runtime_power_state, $row->overall_status]);

        return $tr;
    }

    protected function createPerfColumns($moid)
    {
        return [
            $this->createPerfInOut($moid, 'scsi0:0', 171, 172),
            $this->createPerfInOut($moid, '', 526, 527),
        ];
    }

    protected function createPerfInOut($uuid, $instance, $c1, $c2)
    {
        return DeferredText::create(function () use ($uuid, $instance, $c1, $c2) {
            return new CompactInOutSparkline(
                $this->getVmValues($uuid, $instance, $c1),
                $this->getVmValues($uuid, $instance, $c2)
            );
        })->setEscaped();
    }

    protected function getVmValues($name, $instance, $counter)
    {
        if ($this->perf === null) {
            $this->perf = $this->fetchPerf();
        }

        if (array_key_exists($name, $this->perf)
            && array_key_exists($instance, $this->perf[$name])
            && array_key_exists($counter, $this->perf[$name][$instance])
        ) {
            return $this->perf[$name][$instance][$counter];
        } else {
            return null;
        }
    }

    protected function fetchPerf()
    {
        $db = $this->db();

        $values = implode(" || ',' || ", [
            'value_minus4',
            'value_minus3',
            'value_minus2',
            'value_minus1',
            'value_last',
        ]);

        $query = $db->select()->from('counter_300x5', [
            'name' => 'object_uuid',
            'instance',
            'counter_key',
            'value' => $values,
        ])->where('vm_uuid IN (?)', $this->requiredVms)
            ->where('instance IN (?)', ['', 'scsi0:0'])
            ->where('counter_key IN (?)', array_keys($this->counters));

        $rows = $db->fetchAll($query);

        $result = [];
        foreach ($rows as $row) {
            $result[$row->name][$row->instance][$row->counter_key] = $row->value;
        }

        return $result;
    }

    protected function requireVm($name)
    {
        $this->requiredVms[] = $name;
    }
}
