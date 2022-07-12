<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object describes the Transmission Control Protocol (TCP) host bus adapter interface
 *
 * @since vSphere API 7.0.3.0
 */
class HostTcpHba extends HostHostBusAdapter
{
    /**
     * Device name of the associated physical NIC, if any
     *
     * Should match the device property of the corresponding physical NIC
     *
     * @var string
     */
    public $associatedPnic;
}
