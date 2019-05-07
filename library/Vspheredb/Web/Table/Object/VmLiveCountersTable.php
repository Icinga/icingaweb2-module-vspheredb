<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use DateTime;
use Exception;
use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Application\Benchmark;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\CompactInOutSparkline;
use ipl\Html\Html;

class VmLiveCountersTable extends NameValueTable
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    /** @var Api */
    protected $api;

    public function __construct(VirtualMachine $vm, Api $api)
    {
        $this->vm = $vm;
        $this->api = $api;
    }

    protected function getDb()
    {
        return $this->vm->getConnection();
    }

    protected function assemble()
    {
        $this->addLiveCounters();
    }

    protected function addLiveCounters()
    {
        $vm = $this->vm;
        $uuid = $vm->get('uuid');

        $info = [
            526 => 'Data receive rate',
            527 => 'Data transmit rate',
            543 => 'Read Latency',
            544 => 'Write Latency',
            171 => 'Average Read/s',
            172 => 'Average Write/s',
        ];

        $units = [
            526 => 'kByte/s',
            527 => 'kByte/s',
            543 => 'µs',
            544 => 'µs',
            171 => 'average reads/s',
            172 => 'average writes/s',
        ];

        try {
            $interval = 20;
            $someData = $this->fetchSomePerfdata($interval);
            $someData = $someData[0];
            Benchmark::measure('Got data from vCenter');
            $times = array_values(
                array_filter(
                    preg_split('/,/', $someData->sampleInfoCSV),
                    function ($val) use ($interval) {
                        return $val !== (string) $interval;
                    }
                )
            );

            $first = new DateTime(array_shift($times));
            $last = new DateTime(array_pop($times));
            $first = (int) $first->format('U') * 1000;
            $last = (int) $last->format('U') * 1000;
            foreach ($someData->value as $data) {
                $this->addNameValueRow(
                    sprintf(
                        '%s (%s)',
                        $data->id->instance,
                        $info[$data->id->counterId]
                    ),
                    [
                        Html::tag('span', [
                            'class'      => 'sparkline overspark',
                            'sparkType'  => 'line',
                            'data-first' => $first,
                            'data-last'  => $last,
                            'data-interval' => $interval,
                            'values' => $data->value
                        ]),
                        Html::tag('span', [
                            'class' => 'sparkinfo'
                        ]),
                        Html::tag('span', null, ' ' . $units[$data->id->counterId])
                    ]
                );
            }
        } catch (Exception $e) {
            $this->addNameValueRow('ERROR', $e->getMessage());
        }
        foreach ($this->fetchPerf($uuid) as $instance => $perf) {
            $this->addNameValueRow($instance, $perf);
        }
    }

    protected function fetchPerf($uuid)
    {
        $db = $this->getDb()->getDbAdapter();

        $values = implode(" || ',' || ", [
            'value_minus4',
            'value_minus3',
            'value_minus2',
            'value_minus1',
            'value_last',
        ]);

        $query = $db->select()->from('counter_300x5', [
            'instance',
            'counter_key',
            'value' => $values,
        ])->where('object_uuid = ?', $uuid)
            ->where('counter_key IN (?)', [171, 172, 526, 527])
            ->order('counter_key')->order('instance');

        $rows = $db->fetchAll($query);

        $result = [];
        foreach ($rows as $row) {
            $result[$row->instance][$row->counter_key] = $row->value;
        }

        $final = [];

        foreach ($result as $instance => $entries) {
            $in = array_shift($entries);
            $out = array_shift($entries);
            $final[$instance] = new CompactInOutSparkline($in, $out);
        }

        return $final;
    }

    protected function fetchSomePerfdata($interval)
    {
        $raw = $this->api->perfManager()->queryPerf(
            $this->vm->object()->get('moref'),
            'VirtualMachine',
            $interval,
            600
        );

        return $raw->returnval;
    }
}
