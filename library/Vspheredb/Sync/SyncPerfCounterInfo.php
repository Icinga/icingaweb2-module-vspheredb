<?php

namespace Icinga\Module\Vspheredb\Sync;

use Icinga\Application\Benchmark;
use Icinga\Module\Vspheredb\Api;
use Icinga\Module\Vspheredb\Db;

class SyncPerfCounterInfo
{
    /** @var Api */
    protected $api;

    /** @var Db */
    protected $db;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $dba;

    protected $table = 'counter_300x5';

    public function __construct(Api $api, Db $db)
    {
        $this->api = $api;
        $this->db = $db;
        $this->dba = $db->getDbAdapter();
    }

    public function run()
    {
        foreach ($this->api->perfManager()->getPerformanceCounterInfo() as $prop) {
            switch ($prop->name) {
                case 'description':
                    // counterType(s) and statsType(s), we use ENUMs
                    break;
                case 'historicalInterval':
                    // $this->processHistoricalIntervals($prop->val->PerfInterval);
                    break;
                case 'perfCounter':
                    $this->processCounterInfo($prop->val->PerfCounterInfo);
                    break;
            }
        }
    }

    /**
     * TODO: really sync
     *
     * @param $info
     */
    protected function processCounterInfo($info)
    {
        $uuid = $this->api->getBinaryUuid();
        $db = $this->dba;
        $units = [];
        $groups = [];
        $data = [];
        Benchmark::measure('Preparing Counters for DB');
        foreach ($info as $c) {
            $name  = $c->nameInfo->key;
            $group = $c->groupInfo->key;
            $unit  = $c->unitInfo->key;

            if (! array_key_exists($group, $groups)) {
                $groups[$group] = [
                    'name'    => $group,
                    'label'   => $c->groupInfo->label,
                    'summary' => $c->groupInfo->summary,
                ];
            }
            if (! array_key_exists($unit, $units)) {
                $units[$unit] = [
                    'name'    => $unit,
                    'label'   => $c->unitInfo->label,
                    'summary' => $c->unitInfo->summary,
                ];
            }
            $current = [
                'vcenter_uuid'     => $uuid,
                'counter_key'      => $c->key,
                'name'             => $name,
                'group_name'       => $group,
                'unit_name'        => $unit,
                'label'            => $c->nameInfo->label,
                'summary'          => $c->nameInfo->summary,
                'rollup_type'      => (string) $c->rollupType,
                'stats_type'       => (string) $c->statsType,
                'level'            => $c->level,
                'per_device_level' => $c->perDeviceLevel,
            ];
            $data[] = $current;

            if (in_array($c->key, [78, 434])) {
                print_r($current);
                print_r($c);
            }
        }

        Benchmark::measure('Ready to store Counters to DB');
        $db->beginTransaction();
        foreach ($groups as $group) {
            // $db->insert('performance_group', $group);
        }
        foreach ($units as $unit) {
            // $db->insert('performance_unit', $unit);
        }
        foreach ($data as $c) {
            $db->insert('performance_counter', $c);
        }
        $db->commit();
        Benchmark::measure('Counters stored to DB');
    }

    protected function processHistoricalIntervals($intervals)
    {
        $db = $this->dba;
        foreach ($intervals as $interval) {
            $db->insert('performance_interval', [
                'name' => $interval->key, // 1 ... 4
                'sampling_period' => $interval->samplingPeriod, // 300 ... 86400
                'label' => $interval->name, // 'Past day' ... 'Past year'
                'length' => $interval->length, //  86400 ... 31536000
                'level' => $interval->level, // reserved name! 1, 2...
                'enabled' => $interval->enabled, // 1/0 to y/n
            ]);
        }
    }
}
