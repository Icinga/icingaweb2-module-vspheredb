<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use gipfl\Json\JsonSerialization;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

/**
 * https://www.vmware.com/support/developer/converter-sdk/conv61_apireference/vim.cluster.DasFdmHostState.html
 *
 * The ClusterDasFdmHostState data object describes the availability state of
 * each active host in a vSphere HA enabled cluster.
 *
 * In a vSphere HA cluster, the active hosts form a fault domain. A host is
 * inactive if it is in standby or maintenance mode, or it has been disconnected
 * from vCenter Server. A vSphere HA agent, called the Fault Domain Manager
 * (FDM), runs on each host in the fault domain.
 *
 * One FDM serves as the master and the remaining FDMs as its slaves. The master
 * is responsible for monitoring the availability of the hosts and VMs in the
 * cluster, and restarting any VMs that fail due to a host failure or
 * non-user-initiated power offs. The master is also responsible for reporting
 * fault-domain state to vCenter Server.
 *
 * The master FDM is determined through election by the FDMs that are alive at
 * the time. An election occurs in the following circumstances:
 *
 * - When the vSphere HA feature is enabled for the cluster.
 * - When the master's host fails.
 * - When the management network is partitioned. In a network partition there
 *   will be a master for each partition. However, only one master will be
 *   responsible for a given VM. When the partition is resolved, all but one
 *   of the masters will abdicate.
 * - After a host in a vSphere HA cluster powers back up following a failure
 *   that caused all hosts in the cluster to power off.
 *
 * The slaves are responsible for reporting state updates to the master and
 * restarting VMs as required. All FDMs provide the VM/Application Health
 * Monitoring Service.
 */
#[\AllowDynamicProperties]
class ClusterDasFdmHostState implements JsonSerialization
{
    /**
     * The Availability State of a host based on information reported by the
     * entity given by the stateReporter property.
     *
     * See ClusterDasFdmAvailabilityState for the set of states:
     *
     * - connectedToMaster: The normal operating state for a slave host. In
     *   this state, the host is exchanging heartbeats with a master over
     *   the management network, and is thus connected to it. If there is a
     *   management network partition, the slave will be in this state only
     *   if it is in the same partition as the master. This state is reported
     *   by the master of a slave host.
     * - election: The Fault Domain Manager on the host has been initialized
     *   and the host is either waiting to join the existing master or is
     *   participating in an election for a new master. This state is reported
     *   by vCenter Server or by the host itself.
     * - fdmUnreachable: The Fault Domain Manager (FDM) on the host cannot be
     *   reached. This state is reported in two unlikley situations.
     *   - First, it is reported by a master if the host responds to ICMP pings
     *     sent by the master over the management network but the FDM on the
     *     host cannot be reached by the master. This situation will occur if
     *     the FDM is unable to run or exit the uninitialized state.
     *   - Second, it is reported by vCenter Server if it cannot connect to a
     *     master nor the FDM for the host. This situation would occur if all
     *     hosts in the cluster failed but vCenter Server is still running. It
     *     may also occur if all FDMs are unable to run or exit the uninitialized
     *     state.
     * - hostDown: The slave host appears to be down. This state is reported by
     *   the master of a slave host.
     * - initializationError: An error occurred when initilizating the Fault
     *   Domain Manager on a host due to a problem with installing the agent or
     *   configuring it. This condition can often be cleared by reconfiguring HA
     *   for the host. This state is reported by vCenter Server.
     * - master: The Fault Domain Manager on the host has been elected a master.
     *   This state is reported by the the host itself.
     * - networkIsolated: A host is alive but is isolated from the management
     *   network. See ClusterDasVmSettingsIsolationResponse for the criteria
     *   used to determine whether a host is isolated.
     * - networkPartitionedFromMaster: A slave host is alive and has management
     *   network connectivity, but the management network has been partitioned.
     *   This state is reported by masters that are in a partition other than
     *   the one containing the slave host; the master in the slave's partition
     *   will report the slave state as connectedToMaster.
     * - uninitializationError: An error occurred when unconfiguring the Fault
     *   Domain Manager running on a host. In order to clear this condition the
     *   host might need to be reconnected to the cluster and reconfigured first.
     *   This state is reported by vCenter Server.
     * - uninitialized: The Fault Domain Manager for the host has not yet been
     *   initialized. Hence the host is not part of a vSphere HA fault domain.
     *   This state is reported by vCenter Server or by the host itself.
     *
     * @var string
     */
    public $state;

    /** @var ManagedObjectReference|null */
    public $stateReporter;

    public static function fromSerialization($any)
    {
        $self = new static;
        $self->state = $any->state;
        $self->stateReporter = $any->stateReporter;

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) [
            'state' => $this->state,
            'stateReporter' => $this->stateReporter,
        ];
    }
}
