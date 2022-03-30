<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Exception;
use http\Exception\InvalidArgumentException;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\MappedClass\PerfCounterInfo;
use Icinga\Module\Vspheredb\MappedClass\PerformanceManager;
use Icinga\Module\Vspheredb\SyncRelated\SyncHelper;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;

class PerfCounterInfoSyncStore extends SyncStore
{
    use SyncHelper;

    public function store($result, $class, SyncStats $stats)
    {
        if (! $result instanceof PerformanceManager) {
            throw new InvalidArgumentException('PerformanceManager expected, got: ' . var_export($result, 1));
        }

        $this->processCounterInfo($result->getPerfCounter(), $stats);
    }

    /**
     * TODO: really sync
     *
     * @param PerfCounterInfo[] $infos
     * @throws Exception
     */
    protected function processCounterInfo(array $infos, SyncStats $stats)
    {
        $uuid = $this->vCenter->get('uuid');
        $db = $this->vCenter->getDb();
        $units = [];
        $groups = [];
        $counters = [];
        $this->logger->debug('Preparing Counters for DB');
        foreach ($infos as $info) {
            $name  = $info->nameInfo->key;
            $group = $info->groupInfo->key;
            $unit  = $info->unitInfo->key;

            if (! array_key_exists($group, $groups)) {
                $groups[$group] = [
                    'vcenter_uuid' => $uuid,
                    'name'    => $group,
                    'label'   => $info->groupInfo->label,
                    'summary' => $info->groupInfo->summary,
                ];
            }
            if (! array_key_exists($unit, $units)) {
                $units[$unit] = [
                    'vcenter_uuid' => $uuid,
                    'name'    => $unit,
                    'label'   => $info->unitInfo->label,
                    'summary' => $info->unitInfo->summary,
                ];
            }
            $counter = [
                'vcenter_uuid'     => $uuid,
                'counter_key'      => $info->key,
                'name'             => $name,
                'group_name'       => $group,
                'unit_name'        => $unit,
                'label'            => $info->nameInfo->label,
                'summary'          => $info->nameInfo->summary,
                'rollup_type'      => (string) $info->rollupType,
                'stats_type'       => (string) $info->statsType,
                'level'            => isset($info->level) ? $info->level : 0, // ESXi? Check docs!
                'per_device_level' => isset($info->perDeviceLevel) ? $info->perDeviceLevel : 0,
            ];
            $counters[] = $counter;
        }

        // This is not a full sync, but good enough for our needs.
        self::runAsTransaction($db, function () use ($db, $uuid, $groups, $units, $counters, $stats) {
            $existingGroups = $db->fetchPairs(
                $db->select()
                    ->from('performance_group', ['name', '(1)'])
                    ->where('vcenter_uuid = ?', DbUtil::quoteBinaryCompat($uuid, $db))
            );
            $existingUnits = $db->fetchPairs(
                $db->select()
                    ->from('performance_unit', ['name', '(1)'])
                    ->where('vcenter_uuid = ?', DbUtil::quoteBinaryCompat($uuid, $db))
            );
            $existingCounters = $db->fetchPairs(
                $db->select()
                    ->from('performance_counter', ['counter_key', '(1)'])
                    ->where('vcenter_uuid = ?', DbUtil::quoteBinaryCompat($uuid, $db))
            );
            foreach ($groups as $group) {
                if (! isset($existingGroups[$group['name']])) {
                    $db->insert('performance_group', $group);
                    $stats->incCreated();
                }
            }
            foreach ($units as $unit) {
                if (! isset($existingUnits[$unit['name']])) {
                    $db->insert('performance_unit', $unit);
                    $stats->incCreated();
                }
            }
            foreach ($counters as $info) {
                if (! isset($existingCounters[$info['counter_key']])) {
                    $db->insert('performance_counter', $info);
                    $stats->incCreated();
                }
            }
            $stats->setFromApi(count($groups) + count($units) + count($counters));
            $stats->setFromDb(count($existingGroups) + count($existingUnits) + count($existingCounters));
        });
    }
}
