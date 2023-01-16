<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * A Port data object type is a runtime representation of network connectivity
 * between a network service or virtual machine and a virtual switch. This is
 * different from a port group in that the port group represents the
 * configuration aspects of the network connection. The Port object provides
 * runtime statistics.
 */
#[\AllowDynamicProperties]
class HostPortGroupPort
{
    /**
     * The linkable identifier
     *
     * @var string
     */
    public $key;

    /**
     * The Media Access Control (MAC) address of network service of the virtual
     * machine connected on this port
     *
     * @var string[]
     */
    public $mac;

    /**
     * The type of component connected on this port. Must be one of the values
     * of PortGroupConnecteeType:
     *
     * - host             : The VMkernel is connected to this port group.
     * - systemManagement : A system management entity (service console) is
     *                      connected to this port group.
     * - unknown          : This port group serves an entity of unspecified kind
     * - virtualMachine   : A virtual machine is connected to this port group
     *
     * @var string|null
     */
    public $type;
}
