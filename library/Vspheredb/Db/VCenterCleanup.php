<?php

namespace Icinga\Module\Vspheredb\Db;

use Exception;
use gipfl\ZfDb\Adapter\Adapter;
use Icinga\Module\Vspheredb\Db;
use InvalidArgumentException;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class VCenterCleanup
{
    protected Db $connection;
    protected int $vCenterId;
    protected array $scheduledQueries = [];
    protected string $vCenterUuid;
    protected ?Deferred $deferred = null;
    /** @var \Zend_Db_Adapter_Abstract|Adapter */
    protected $db;

    public function __construct(Db $connection, int $vCenterId)
    {
        $this->connection = $connection;
        $this->db = $connection->getDbAdapter();
        $this->vCenterId = $vCenterId;
        $uuid = $this->db->fetchOne($this->db->select()->from('vcenter', 'instance_uuid')->where('id = ?', $vCenterId));
        if (!is_string($uuid) || strlen($uuid) !== 16) {
            throw new InvalidArgumentException('Found no vCenter with id ' . $vCenterId);
        }

        $this->vCenterUuid = $uuid;
    }

    public function run(): PromiseInterface
    {
        if ($this->deferred !== null) {
            throw new RuntimeException('Cannot run() vCenter deletion twice');
        }
        $this->deferred = new Deferred();
        $this->scheduleQueries();
        Loop::futureTick(fn () => $this->tick());

        return $this->deferred->promise();
    }

    protected function tick(): void
    {
        $query = array_shift($this->scheduledQueries);
        if ($query === null) {
            if ($this->deferred) {
                // Should never be null, this is just a safety measure
                $this->deferred->resolve(true);
            }
            return;
        }

        try {
            $this->db->query($query[0], $query[1]);
            Loop::futureTick(fn () => $this->tick());
        } catch (\Throwable $e) {
            $deferred = $this->deferred;
            $this->scheduledQueries = [];
            $this->deferred = null;
            $deferred->reject(new \Exception(sprintf(
                "Query %s failed: %s",
                $query[0],
                $e->getMessage()
            )));
        }
    }

    protected function scheduleQueries(): void
    {
        $uuid = $this->vCenterUuid;
        $this->scheduledQueries = [
            ['DELETE FROM vspheredb_daemonlog WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vspheredb_daemonlog', []],
            ['DELETE FROM vcenter_sync WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vcenter_sync', []],
            ['DELETE FROM compute_resource WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE compute_resource', []],
            ['DELETE FROM host_virtual_nic WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE host_virtual_nic', []],
            ['DELETE FROM host_physical_nic WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE host_physical_nic', []],
            ['DELETE FROM host_hba WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE host_hba', []],
            ['DELETE FROM host_sensor WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE host_sensor', []],
            ['DELETE FROM host_pci_device WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE host_pci_device', []],
            ['DELETE FROM host_quick_stats WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE host_quick_stats', []],
            ['DELETE FROM host_system WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE host_system', []],
            // host_monitoring_hoststate -> cascade
            ['DELETE FROM vm_snapshot WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vm_snapshot', []],
            ['DELETE FROM vm_datastore_usage WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vm_datastore_usage', []],
            ['DELETE FROM vm_hardware WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vm_hardware', []],
            ['DELETE FROM vm_disk WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vm_disk', []],
            ['DELETE FROM vm_disk_usage WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vm_disk_usage', []],
            ['DELETE FROM vm_network_adapter WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vm_network_adapter', []],
            ['DELETE FROM vm_quick_stats WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vm_quick_stats', []],
            ['DELETE FROM vm_event_history WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE vm_event_history', []],
            ['DELETE FROM virtual_machine WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE virtual_machine', []],
            // vm_monitoring_hoststate -> cascade
            ['DELETE FROM alarm_history WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE alarm_history', []],
            ['DELETE FROM monitoring_connection WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE monitoring_connection', []],
            ['DELETE FROM counter_300x5 WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE counter_300x5', []],
            ['DELETE FROM performance_counter WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE performance_counter', []],
            ['DELETE FROM performance_unit WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE performance_unit', []],
            ['DELETE FROM performance_group WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE performance_group', []],
            ['DELETE FROM performance_collection_interval WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE performance_collection_interval', []],
            ['DELETE FROM perfdata_subscription WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE perfdata_subscription', []],
            ['DELETE FROM tagging_category WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE tagging_category', []],
            ['DELETE FROM tagging_tag WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE tagging_tag', []],
            ['DELETE FROM tagging_object_tag WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE tagging_object_tag', []],
            ['DELETE FROM distributed_virtual_switch WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE distributed_virtual_switch', []],
            ['DELETE FROM distributed_virtual_portgroup WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE distributed_virtual_portgroup', []],
            ['DELETE FROM storage_pod WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE storage_pod', []],
            ['DELETE FROM datastore WHERE vcenter_uuid = ?;', [$uuid]],
            ['OPTIMIZE TABLE datastore', []],
            ['DELETE d FROM host_list_member d LEFT JOIN object o ON d.uuid = o.uuid WHERE o.uuid IS NULL;', []],
            ['OPTIMIZE TABLE host_list_member', []],
            ['DELETE d FROM vm_list_member d LEFT JOIN object o ON d.uuid = o.uuid WHERE o.uuid IS NULL;', []],
            ['OPTIMIZE TABLE vm_list_member', []],
            [
                'DELETE d FROM monitoring_rule_set d LEFT JOIN object o ON d.object_uuid = o.uuid'
                . ' WHERE o.uuid IS NULL;',
                []
            ],
            ['OPTIMIZE TABLE monitoring_rule_set', []],
            ['DELETE FROM object WHERE vcenter_uuid = ? ORDER BY level DESC;', [$uuid]],
            ['OPTIMIZE TABLE object', []],
            ['DELETE FROM vcenter WHERE id = ?;', [$this->vCenterId]],
            ['OPTIMIZE TABLE vcenter', []],
        ];
    }
}
