<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Module\Vspheredb\PropertySet\PropertySet;
use Icinga\Module\Vspheredb\SelectSet\SelectSet;

class PerfManager
{
    /** @var Api */
    protected $api;

    protected $obj;

    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->obj = $api->getServiceInstance()->perfManager;
    }

    public function collectPerfdata($specSet)
    {
        $specSet = [
            '_this'   => $this->obj,
            'specSet' => $specSet
        ];

        // TODO: test whether this works
        return $this->api->soapCall('RetrieveProperties', $specSet);

        // return $this->makeNiceResult(
        //     $this->api->soapCall('RetrieveProperties', $specSet)
        // );
    }

    public function collectObjectPerfdata(PropertySet $propSet, SelectSet $selectSet)
    {
        $result = $this->collectPerfdata(
            $this->api->makePropertyFilterSpec($propSet, $selectSet)
        );

        return $result;
    }

    public function queryPerfProviderSummary($name, $type)
    {
        $specSet = [
            '_this'  => $this->obj,
            'entity' => $this->makeEntity($name, $type),
        ];

        // returnval => {
        //   entity => { _ => host-326963, type => HostSystem }
        //   currentSupported => 1
        //   summarySupported => 1
        //   refreshRate => 20
        // }

        return $this->api->soapCall('QueryPerfProviderSummary', $specSet);
    }

    // $api->queryAvailablePerfMetric('datacenter-21', 'Datacenter', 20));
    // $api->queryAvailablePerfMetric('host-326963', 'HostSystem', 20) );
    // $api->queryAvailablePerfMetric('vm-844012', 'VirtualMachine', 20));
    public function queryAvailablePerfMetric($name, $type, $intervalId)
    {
        $specSet = [
            '_this'  => $this->obj,
            'entity' => $this->makeEntity($name, $type),
            'interval' => $intervalId // intervalId?? beginTime, endTime
        ];

        return $this->api->soapCall('QueryAvailablePerfMetric', $specSet);
    }

    public function queryBulkPerfByName($what, $type, $names)
    {
        // $metrics =
        // virtualDisk.numberReadAveraged.average => '',
        // virtualDisk.numberWriteAveraged.average => ''
        // net.bytesRx.average
    }

    public function queryPerf($name, $type, $interval = 20, $count = 60)
    {
        $metrics = [
            (object) ['counterId' => 526, 'instance' => '*'],
            (object) ['counterId' => 527, 'instance' => '*'],
            (object) ['counterId' => 171, 'instance' => '*'],
            (object) ['counterId' => 172, 'instance' => '*'],
            (object) ['counterId' => 543, 'instance' => '*'],
            (object) ['counterId' => 544, 'instance' => '*'],
        ];

        return $this->fetchMetrics($metrics, $name, $type, $interval, $count);
    }

    public function getPerformanceCounterInfo()
    {
        $objects = $this->api->propertyCollector()->collectPropertiesEx([
            'propSet' => [
                'type' => 'PerformanceManager',
                'all'  => true
            ],
            'objectSet' => [
                'obj' => $this->obj,
                'skip' => false
            ]
        ]);

        return $objects->returnval->objects[0]->propSet;
    }

    protected function makeDateTime($timestamp)
    {
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    /**
     * WTF?! PHP 7 creates href/refX links when adding the same array multiple times
     *
     * @param $metrics
     * @return array
     */
    protected function cloneMetrics($metrics)
    {
        $clone = [];
        foreach ($metrics as $m) {
            $clone[] = clone($m);
        }

        return $clone;
    }

    protected function fetchMetrics($metrics, $names, $type, $interval, $count)
    {
        $api = $this->api;
        $duration = $interval * ($count);
        $now = floor(time() / $interval) * $interval;

        $start = $this->makeDateTime($now - $duration);
        $end = $this->makeDateTime($now);

        if (is_array($names)) {
            $spec = [];
            foreach ($names as $name) {
                $spec[] = [
                    'entity'     => $this->makeEntity($name, $type),
                    'startTime'  => $start,
                    'endTime'    => $end, //'2017-12-13T18:10:00Z',
                    // 'maxSample' => $count,
                    'metricId'   => $this->cloneMetrics($metrics),
                    'intervalId' => $interval,
                    'format'     => 'csv'
                ];
            }
        } else {
            $spec = [
                'entity'     => $this->makeEntity($names, $type),
                'startTime'  => $start,
                'endTime'    => $end, //'2017-12-13T18:10:00Z',
                // 'maxSample' => $count,
                'metricId'   => $this->cloneMetrics($metrics),
                'intervalId' => $interval,
                'format'     => 'csv'
            ];
        }

        $specSet = [
            '_this'  => $api->getServiceInstance()->perfManager,
            'querySpec' => $spec
        ];

        return $api->soapCall('QueryPerf', $specSet);
    }

    public function makeEntity($name, $type)
    {
        return ['_' => $name, 'type' => $type];
    }

    // Might be obsolete, if not please unify with the one in the PropertyCollector
    protected function makeNiceResult($result)
    {
        if (! property_exists($result, 'returnval')) {
            return [];
        }

        $knownRefs = [
            'parent',
            'runtime.host',
        ];

        $nice = [];
        foreach ($result->returnval as $row) {
            $data = [
                'id'   => $row->obj->_,
                'type' => $row->obj->type
            ];
            foreach ($row->propSet as $prop) {
                $val = $prop->val;
                if (in_array($prop->name, $knownRefs)) {
                    // [parent] => stdClass Object (
                    //    [_] => group-v123456
                    //    [type] => Folder, HostSystem etc
                    // )
                    $data[$prop->name] = $val->_;
                } else {
                    if (is_object($val) && property_exists($val, 'ManagedObjectReference')) {
                        $val = $this->flattenReference($val->ManagedObjectReference);
                    }
                    $data[$prop->name] = $val;
                }
            }
            $nice[$row->obj->_] = (object) $data;
        }

        return $nice;
    }

    protected function flattenReference($ref)
    {
        $res = [];
        foreach ($ref as $r) {
            $res[] = $r->_;
        }

        return $res;
    }
}
