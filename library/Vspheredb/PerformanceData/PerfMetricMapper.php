<?php

namespace Icinga\Module\Vspheredb\PerformanceData;

use Icinga\Module\Vspheredb\MappedClass\PerfEntityMetricCSV;
use Icinga\Module\Vspheredb\MappedClass\PerfMetricId;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class PerfMetricMapper
{
    protected $vCenter;

    protected $counters;

    protected $prefix;

    public function __construct($counters, $prefix)
    {
        $this->counters = $counters;
        $this->prefix = $prefix;
    }

    public function process(PerfEntityMetricCSV $metric)
    {
        $object = $metric->entity;
        $dates = $this->parseDates($metric);
        $result = [];
        foreach ($metric->value as $series) {
            $key = $this->makeKey($object, $series->id);
            $metric = $this->getCounterIdName($series->id->counterId);
            foreach (array_combine(
                $dates,
                preg_split('/,/', $series->value)
            ) as $time => $value) {
                $result[$time][$key][$metric] = $value;
            }
        }

        return $result;
    }

    public function makeInfluxLineFormat(PerfEntityMetricCSV $metric)
    {
        $lines = '';
        foreach ($this->process($metric) as $ts => $values) {
            foreach ($values as $file => $metrics) {
                $lines .= $file;
                foreach ($metrics as $metric => $value) {
                    $lines .= " $metric=$value";
                }

                // $lines .= " ${ts}000\n";
                $lines .= printf(" %d\n", (int) round($ts / 1000));
            }
        }

        return $lines;
    }

    protected function makeKey(ManagedObjectReference $ref, PerfMetricId $id)
    {
        $ref = $ref->_;

        return $ref . '/'
            . (strlen($id->instance) ? $this->prefix . $id->instance : $this->prefix);
    }

    protected function getCounterIdName($id)
    {

        return $this->counters[$id];
    }

    protected function parseDates(PerfEntityMetricCSV $metric)
    {
        $parts = preg_split('/,/', $metric->sampleInfoCSV);
        $max = count($parts) - 1;
        $dates = [];
        for ($i = 1; $i <= $max; $i += 2) {
            $dates[] = Util::timeStringToUnixMs($parts[$i]);
        }

        return $dates;
    }
}
