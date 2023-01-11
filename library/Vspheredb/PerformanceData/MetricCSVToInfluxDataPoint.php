<?php

namespace Icinga\Module\Vspheredb\PerformanceData;

use Generator;
use gipfl\InfluxDb\DataPoint;
use Icinga\Module\Vspheredb\MappedClass\PerfEntityMetricCSV;
use Icinga\Module\Vspheredb\MappedClass\PerfMetricId;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use InvalidArgumentException;
use function array_combine;
use function count;
use function explode;
use function implode;

class MetricCSVToInfluxDataPoint
{
    public static function map(
        string              $measurementName,
        PerfEntityMetricCSV $metric,
        array               $countersMap,
        array               $tags
    ): Generator {
        $object = $metric->entity;
        $dates = static::parseDates($metric);
        $result = [];
        foreach ($metric->value as $series) {
            $key = static::makeKey($object, $series->id);
            $metric = $countersMap[$series->id->counterId];
            foreach (array_combine(
                $dates,
                explode(',', $series->value)
            ) as $time => $value) {
                $result[$time][$key][$metric] = $value === '' ? null : (int) $value;
            }
        }
        foreach ($result as $time => $results) {
            foreach ($results as $key => $metrics) {
                if (! isset($tags[$key])) {
                    if (count($tags) > 10) {
                        $tagList = implode(', ', array_slice(array_keys($tags), 0, 10)) . ', ...';
                    } else {
                        $tagList = implode(', ', array_keys($tags));
                    }
                    throw new InvalidArgumentException("Cannot find tags for '$key', got: $tagList");
                }
                yield new DataPoint(
                    $measurementName,
                    ['instance' => $key] + $tags[$key],
                    $metrics,
                    $time
                );
            }
        }
    }

    protected static function makeKey(ManagedObjectReference $ref, PerfMetricId $id): string
    {
        $ref = $ref->_;
        if ($id->instance !== null && strlen($id->instance)) {
            return "$ref/" . $id->instance;
        }

        return $ref;
    }

    protected static function parseDates(PerfEntityMetricCSV $metric): array
    {
        $parts = explode(',', $metric->sampleInfoCSV);
        $max = count($parts) - 1;
        $dates = [];
        for ($i = 1; $i <= $max; $i += 2) {
            $dates[] = Util::timeStringToUnixTime($parts[$i]);
        }

        return $dates;
    }
}
