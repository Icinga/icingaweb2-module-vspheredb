<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

/**
 * Hint: a VmwareDistributedVirtualSwitch is also a DistributedVirtualSwitch,
 * but with more capabilities
 */
class DistributedVirtualSwitch extends BaseDbObject
{
    protected $keyName = 'uuid';

    protected $table = 'distributed_virtual_switch';

    protected $defaultProperties = [
        'uuid'                 => null,
        'vcenter_uuid'         => null,
        'description'          => null,
        'num_hosts'            => null,
        'num_ports'            => null,
        'max_ports'            => null,
        'hostmembers_checksum' => null,
        'portgroups_checksum'  => null,
        'vms_checksum'         => null,
    ];

    protected $propertyMap = [
        // 'portgroup'           => 'portGroups',
        'config.description'     => 'description',
        // config.uuid?
        'summary.hostMember'     => 'hostMembers',
        // 'summary.vm'          => 'vms',
        'summary.numHosts'       => 'num_hosts',
        'config.numPorts'        => 'num_ports',
        'config.maxPorts'        => 'max_ports',
        'config.uplinkPortgroup' => 'uplinkPortGroups',
    ];

    protected $unstoredPortGroupRefs;

    public function setUplinkPortGroups($portGroups)
    {
        var_dump('UPLINK');
        var_dump($portGroups);
    }

    public function XXXXsetPortGroups($portGroups)
    {
        $newSum = $this->calculateMorefsChecksum($portGroups);
        if ($this->get('portgroups_checksum') !== $newSum) {
            $this->scheduleNewPortgroupRefs($portGroups);
        }
    }

    /**
     * @param ManagedObjectReference[] $hostMembers
     */
    public function setHostMembers($hostMembers)
    {
        var_dump('HOSTMEMBERS');
        var_dump($hostMembers);
        return;
        $newSum = $this->calculateMorefsChecksum($hostMembers);
        if ($this->get('hostmembers_checksum') !== $newSum) {
            $this->scheduleNewPortgroupRefs($hostMembers);
        }
    }

    protected function scheduleNewPortgroupRefs($portGroups)
    {
        $this->unstoredPortGroupRefs = $portGroups;
    }

    protected function onStore()
    {
        if (false && $this->unstoredPortGroupRefs) {
            $this->replaceMoRefs(
                $this->get('uuid'),
                'distributed_switch_portgroup',
                $this->unstoredPortGroupRefs
            );
        }
    }

    protected function replaceMoRefs($uuid, $table, $refs)
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

    protected function calculateMorefsChecksum($moRefs)
    {
        $names = [];
        foreach ($moRefs as $moRef) {
            $names[] = $moRef->_;
        }

        sort($names);
        return sha1(implode('|', $names), true);
    }
}
