<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

/**
 * Hint: a VmwareDistributedVirtualSwitch is also a DistributedVirtualSwitch,
 * but with more capabilities
 */
class DistributedVirtualSwitch extends BaseDbObject
{
    protected string|array|null $keyName = 'uuid';

    protected ?string $table = 'distributed_virtual_switch';

    protected ?array $defaultProperties = [
        'uuid'                 => null,
        'vcenter_uuid'         => null,
        'description'          => null,
        'num_hosts'            => null,
        'num_ports'            => null,
        'max_ports'            => null,
        'hostmembers_checksum' => null,
        'portgroups_checksum'  => null,
        'vms_checksum'         => null
    ];

    protected array $propertyMap = [
        // 'portgroup'           => 'portGroups',
        'config.description'     => 'description',
        // config.uuid?
        'summary.hostMember'     => 'hostMembers',
        // 'summary.vm'          => 'vms',
        'summary.numHosts'       => 'num_hosts',
        'config.numPorts'        => 'num_ports',
        'config.maxPorts'        => 'max_ports',
        'config.uplinkPortgroup' => 'uplinkPortGroups'
    ];

    /** @var array|null */
    protected ?array $unstoredPortGroupRefs = null;

    /**
     * @param array $portGroups
     *
     * @return void
     */
    public function setUplinkPortGroups(array $portGroups): void
    {
        var_dump('UPLINK');
        var_dump($portGroups);
    }

    /**
     * @param array $portGroups
     *
     * @return void
     */
    public function XXXXsetPortGroups(array $portGroups): void
    {
        $newSum = $this->calculateMorefsChecksum($portGroups);
        if ($this->get('portgroups_checksum') !== $newSum) {
            $this->scheduleNewPortgroupRefs($portGroups);
        }
    }

    /**
     * @param ManagedObjectReference[] $hostMembers
     *
     * @return void
     */
    public function setHostMembers(array $hostMembers): void
    {
        var_dump('HOSTMEMBERS');
        var_dump($hostMembers);

//        return;
//
//        $newSum = $this->calculateMorefsChecksum($hostMembers);
//        if ($this->get('hostmembers_checksum') !== $newSum) {
//            $this->scheduleNewPortgroupRefs($hostMembers);
//        }
    }

    /**
     * @param array $portGroups
     *
     * @return void
     */
    protected function scheduleNewPortgroupRefs(array $portGroups): void
    {
        $this->unstoredPortGroupRefs = $portGroups;
    }

    protected function onStore(): void
    {
//        if (false && $this->unstoredPortGroupRefs) {
//            $this->replaceMoRefs(
//                $this->get('uuid'),
//                'distributed_switch_portgroup',
//                $this->unstoredPortGroupRefs
//            );
//        }
    }

    /**
     * @param string $uuid
     * @param string $table
     * @param array $refs
     *
     * @return void
     */
    protected function replaceMoRefs(string $uuid, string $table, array $refs): void
    {
        // TODO: WHAAAAAAAAAAAAAAT?
        $vCenter = VCenter::loadWithAutoIncId(1, $this->getConnection());
        $db = $this->getDb();
        $db->delete(
            $table,
            $db->quoteInto('object_uuid = ?', $uuid)
        );
        foreach ($refs as $ref) {
            $db->insert($table, [
                'object_uuid' => $uuid,
                'referred_uuid' => $vCenter->makeBinaryGlobalUuid($ref->_)
            ]);
        }
    }

    /**
     * @param array $moRefs
     *
     * @return string
     */
    protected function calculateMorefsChecksum(array $moRefs): string
    {
        $names = [];
        foreach ($moRefs as $moRef) {
            $names[] = $moRef->_;
        }

        sort($names);

        return sha1(implode('|', $names), true);
    }
}
