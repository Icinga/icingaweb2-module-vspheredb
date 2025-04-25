<?php

namespace Icinga\Module\Vspheredb\Polling\SyncStore;

use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VmDatastoreUsage;
use Icinga\Module\Vspheredb\Polling\VsphereApi;
use Icinga\Module\Vspheredb\SyncRelated\SyncHelper;
use Icinga\Module\Vspheredb\SyncRelated\SyncStats;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use Psr\Log\LoggerInterface;

use function React\Promise\all;
use function React\Promise\resolve;

class VmDatastoreUsageSyncStore extends SyncStore
{
    use SyncHelper;

    // Refresh outdated VMs -> before and after?

    public function store($result, $class, SyncStats $stats)
    {
        $vCenter = $this->vCenter;
        $vCenterUuid = $vCenter->getUuid();
        $connection = $vCenter->getConnection();
        $dbObjects = VmDatastoreUsage::loadAllForVCenter($vCenter);

        $seen = [];
        foreach ($result as $map) {
            $map = (object) $map;
            $moRef = $map->obj;
            $vmUuid = $vCenter->makeBinaryGlobalMoRefUuid($moRef);
            if (! isset($map->{'storage.perDatastoreUsage'}->{'VirtualMachineUsageOnDatastore'})) {
                continue;
            }
            if (isset($map->{'storage.timestamp'})) {
                $timestamp = Util::timeStringToUnixMs($map->{'storage.timestamp'});
            } else {
                $timestamp = null;
            }
            foreach ($map->{'storage.perDatastoreUsage'}->{'VirtualMachineUsageOnDatastore'} as $usage) {
                $dsUuid = $vCenter->makeBinaryGlobalMoRefUuid($usage->datastore);
                $key = "$vmUuid$dsUuid";
                $usage = [
                    'committed'   => $usage->committed,
                    'uncommitted' => $usage->uncommitted,
                    'unshared'    => $usage->unshared,
                    'ts_updated'  => $timestamp,
                ];
                $seen[$key] = $key;
                if (array_key_exists($key, $dbObjects)) {
                    $dbObjects[$key]->setProperties($usage);
                } else {
                    $dbObjects[$key] = $class::create([
                        'vm_uuid' => $vmUuid,
                        'datastore_uuid' => $dsUuid,
                        'vcenter_uuid' => $vCenterUuid
                    ] + $usage, $connection);
                }
            }
        }

        $this->storeSyncObjects($connection->getDbAdapter(), $dbObjects, $seen, $stats);
    }

    public static function fetchOutdatedVms(VCenter $vCenter, $lastRefreshSecondsAgo = 1800, $cntMax = null)
    {
        $db = $vCenter->getDb();
        $vCenterUuid = $vCenter->get('uuid');

        // Updated 1800s ago? Outdated.
        $tsExpiredMs = (time() - $lastRefreshSecondsAgo) * 1000;

        $query = $db->select()->from(['o' => 'object'], [
            'moref'       => 'o.moref',
            'object_name' => 'o.object_name',
        ])->join(
            ['vm' => 'virtual_machine'],
            "vm.uuid = o.uuid AND vm.template = 'n' AND vm.runtime_power_state = 'poweredOn'",
            []
        )->join(
            ['vdu' => 'vm_datastore_usage'],
            'vm.uuid = vdu.vm_uuid',
            []
        )->where(
            'o.vcenter_uuid = ?',
            $vCenterUuid
        )->where(
            'vdu.ts_updated < ?',
            $tsExpiredMs
        )->order('vdu.ts_updated ASC')->group('o.uuid');
        if ($cntMax) {
            $query->limit($cntMax);
        }

        return $db->fetchPairs($query);
    }

    public static function refreshOutdatedVms(VsphereApi $api, $vms, LoggerInterface $logger)
    {
        if (empty($vms)) {
            return resolve();
        }

        $logger->info(sprintf(
            'Calling RefreshStorageInfo on %d outdated VirtualMachine(s)',
            count($vms)
        ));

        $pending = [];
        foreach (array_keys($vms) as $moref) {
            $pending[] = $api->call(new ManagedObjectReference('VirtualMachine', $moref), 'RefreshStorageInfo');
        }

        return all($pending);
    }

    protected function makeWhere(\Zend_Db_Adapter_Abstract $db, $vmUuid, $dsUuid)
    {
        return $db->quoteInto('vm_uuid = ?', $vmUuid)
            . $db->quoteInto(' AND datastore_uuid = ?', $dsUuid);
    }
}
