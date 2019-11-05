<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use ipl\Html\DeferredText;
use Icinga\Module\Vspheredb\Web\Table\SimpleColumn;
use Icinga\Module\Vspheredb\Web\Table\TableColumn;

class DelayedPerfdataRenderer
{
    protected $requiredVms = [];

    protected $perf;

    protected $counters = [
        526 => 'net.bytesRx',
        527 => 'net.bytesRx',
        171 => 'virtualDisk.numberReadAveraged',
        172 => 'virtualDisk.numberWriteAveraged',
    ];

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    public function __construct(\Zend_Db_Adapter_Abstract $db)
    {
        $this->db = $db;
    }

    public function requireVm($uuid)
    {
        $this->requiredVms[] = $uuid;
    }

    /**
     * @return TableColumn
     */
    public function getDiskColumn()
    {
        return (new SimpleColumn('disk_io_perf', '5x5min Disk I/O', 'o.uuid'))
            ->setRenderer(function ($row) {
                $this->requireVm($row->uuid);

                return $this->createPerfInOut($row->uuid, 'scsi0:0', 171, 172);
            });
    }

    /**
     * @return TableColumn
     */
    public function getNetColumn()
    {
        return (new SimpleColumn('network_io_perf', 'Network I/O (perf)', 'o.uuid'))
            ->setRenderer(function ($row) {
                $this->requireVm($row->uuid);

                return $this->createPerfInOut($row->uuid, '', 526, 527);
            });
    }

    /**
     * @return TableColumn
     */
    public function getCurrentNetColumn()
    {
        return (new SimpleColumn('network_io', 'Network I/O', 'o.uuid'))
            ->setRenderer(function ($row) {
                $this->requireVm($row->uuid);

                return $this->createKbInOut($row->uuid, '', 526, 527);
            });
    }

    /**
     * @return TableColumn
     */
    public function getCurrentDiskColumn()
    {
        return (new SimpleColumn('disk_io', 'Disk I/O', 'o.uuid'))
            ->setRenderer(function ($row) {
                $this->requireVm($row->uuid);

                return $this->createKbInOut($row->uuid, 'scsi0:0', 171, 172);
            });
    }

    protected function formatMicroSeconds($num)
    {
        if ($num > 500) {
            return sprintf('%0.2Fms', $num / 1000);
        } else {
            return sprintf('%dµs', $num);
        }
    }

    protected function formatKiloBytesPerSecond($num)
    {
        $num *= 8;
        if ($num > 500000) {
            return sprintf('%0.2F Gbit/s', $num / 1024 / 1024);
        } elseif ($num > 500) {
            return sprintf('%0.2F Mbit/s', $num / 1024);
        } else {
            return sprintf('%0.2F Kbit/s', $num);
        }
    }

    protected function createKbInOut($uuid, $instance, $c1, $c2)
    {
        return DeferredText::create(function () use ($uuid, $instance, $c1, $c2) {
            $in = explode(',', $this->getVmValues($uuid, $instance, $c1));
            $out = explode(',', $this->getVmValues($uuid, $instance, $c2));
            if ($in[0] === '') {
                $in = '-';
            } else {
                $in = $this->formatKiloBytesPerSecond(array_pop($in));
            }
            if ($out[0] === '') {
                $out = '-';
            } else {
                $out = $this->formatKiloBytesPerSecond(array_pop($out));
            }

            return sprintf('%s / %s', $in, $out);
        })->setEscaped();
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
        $db = $this->db;

        $values = '(' . implode(" || ',' || ", [
                "COALESCE(value_minus4, '0')",
                "COALESCE(value_minus3, '0')",
                "COALESCE(value_minus2, '0')",
                "COALESCE(value_minus1, '0')",
                'value_last',
            ]) . ')';

        $query = $db->select()->from('counter_300x5', [
            'name' => 'object_uuid',
            'instance',
            'counter_key',
            'value' => $values,
            'value_last'
        ])->where('object_uuid IN (?)', $this->requiredVms)
            ->where('instance IN (?)', ['', 'scsi0:0'])
            ->where('counter_key IN (?)', array_keys($this->counters));

        $rows = $db->fetchAll($query);

        $result = [];
        foreach ($rows as $row) {
            $result[$row->name][$row->instance][$row->counter_key] = $row->value;
        }

        return $result;
    }
}
