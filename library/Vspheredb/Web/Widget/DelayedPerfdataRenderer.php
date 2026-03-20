<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Web\Table\SimpleColumn;
use ipl\Html\DeferredText;
use Zend_Db_Adapter_Abstract;

class DelayedPerfdataRenderer
{
    protected array $requiredVms = [];

    protected ?array $perf = null;

    protected array $counters = [
        526 => 'net.bytesRx',
        527 => 'net.bytesRx',
        171 => 'virtualDisk.numberReadAveraged',
        172 => 'virtualDisk.numberWriteAveraged'
    ];

    /** @var Zend_Db_Adapter_Abstract */
    protected Zend_Db_Adapter_Abstract $db;

    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->db = $db;
    }

    public function requireVm(string $uuid): void
    {
        $this->requiredVms[] = $uuid;
    }

    /**
     * @return SimpleColumn
     */
    public function getDiskColumn(): SimpleColumn
    {
        return (new SimpleColumn('disk_io_perf', '5x5min Disk I/O', 'o.uuid'))
            ->setRenderer(function ($row) {
                $this->requireVm($row->uuid);

                return $this->createPerfInOut($row->uuid, 'scsi0:0', 171, 172);
            });
    }

    /**
     * @return SimpleColumn
     */
    public function getNetColumn(): SimpleColumn
    {
        return (new SimpleColumn('network_io_perf', 'Network I/O (perf)', 'o.uuid'))
            ->setRenderer(function ($row) {
                $this->requireVm($row->uuid);

                return $this->createPerfInOut($row->uuid, '', 526, 527);
            });
    }

    /**
     * @return SimpleColumn
     */
    public function getCurrentNetColumn(): SimpleColumn
    {
        return (new SimpleColumn('network_io', 'Network I/O', 'o.uuid'))
            ->setRenderer(function ($row) {
                $this->requireVm($row->uuid);

                return $this->createKbInOut($row->uuid, '', 526, 527);
            });
    }

    /**
     * @return SimpleColumn
     */
    public function getCurrentDiskColumn(): SimpleColumn
    {
        return (new SimpleColumn('disk_io', 'Disk I/O', 'o.uuid'))
            ->setRenderer(function ($row) {
                $this->requireVm($row->uuid);

                return $this->createKbInOut($row->uuid, 'scsi0:0', 171, 172);
            });
    }

    protected function formatMicroSeconds(int $num): string
    {
        if ($num > 500) {
            return sprintf('%0.2Fms', $num / 1000);
        }

        return sprintf('%dµs', $num);
    }

    protected function formatKiloBytesPerSecond(int $num): string
    {
        $num *= 8;
        return match (true) {
            $num > 500000 => sprintf('%0.2F Gbit/s', $num / 1024 / 1024),
            $num > 500    => sprintf('%0.2F Mbit/s', $num / 1024),
            default       => sprintf('%0.2F Kbit/s', $num)
        };
    }

    /**
     * @param string $uuid
     * @param string $instance
     * @param int $c1
     * @param int $c2
     *
     * @return DeferredText
     */
    protected function createKbInOut(string $uuid, string $instance, int $c1, int $c2): DeferredText
    {
        return DeferredText::create(function () use ($uuid, $instance, $c1, $c2) {
            $in = explode(',', $this->getVmValues($uuid, $instance, $c1));
            $out = explode(',', $this->getVmValues($uuid, $instance, $c2));

            $in = $in[0] === '' ? '-' : $this->formatKiloBytesPerSecond(array_pop($in));
            $out = $out[0] === '' ? '-' : $this->formatKiloBytesPerSecond(array_pop($out));

            return sprintf('%s / %s', $in, $out);
        })->setEscaped();
    }

    /**
     * @param string $uuid
     * @param string $instance
     * @param int $c1
     * @param int $c2
     *
     * @return DeferredText
     */
    protected function createPerfInOut(string $uuid, string $instance, int $c1, int $c2): DeferredText
    {
        return DeferredText::create(function () use ($uuid, $instance, $c1, $c2) {
            return new CompactInOutSparkline(
                $this->getVmValues($uuid, $instance, $c1),
                $this->getVmValues($uuid, $instance, $c2)
            );
        })->setEscaped();
    }

    /**
     * @param string $name
     * @param string $instance
     * @param int $counter
     *
     * @return ?array
     */
    protected function getVmValues(string $name, string $instance, int $counter): ?array
    {
        $this->perf ??= $this->fetchPerf();

        if (
            array_key_exists($name, $this->perf)
            && array_key_exists($instance, $this->perf[$name])
            && array_key_exists($counter, $this->perf[$name][$instance])
        ) {
            return $this->perf[$name][$instance][$counter];
        }

        return null;
    }

    protected function fetchPerf(): array
    {
        $db = $this->db;

        $values = '(' . implode(" || ',' || ", [
                "COALESCE(value_minus4, '0')",
                "COALESCE(value_minus3, '0')",
                "COALESCE(value_minus2, '0')",
                "COALESCE(value_minus1, '0')",
                'value_last'
            ]) . ')';

        $query = $db->select()
            ->from('counter_300x5', [
                'name'  => 'object_uuid',
                'instance',
                'counter_key',
                'value' => $values,
                'value_last'
            ])
            ->where('object_uuid IN (?)', $this->requiredVms)
            ->where('instance IN (?)', ['', 'scsi0:0'])
            ->where('counter_key IN (?)', array_keys($this->counters));

        $rows = $db->fetchAll($query);

        $result = [];
        /** @var object{name: string, instance: string, counter_key: int, value: string} $row */
        foreach ($rows as $row) {
            $result[$row->name][$row->instance][$row->counter_key] = $row->value;
        }

        return $result;
    }
}
