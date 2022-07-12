<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object type describes the Fibre Channel host bus adapter
 */
class HostFibreChannelHba extends HostHostBusAdapter
{
    /** @var int (long) The world wide node name for the adapter */
    public $nodeWorldWideName;

    /**
     * The type of the fiber channel port
     *
     * Enum of type FibreChannelPortType, value is any of:
     *
     * - fabric
     * - loop
     * - pointToPoint
     * - unknown
     *
     * @var string
     */
    public $portType;

    /** @var int (long) The world wide port name for the adapter */
    public $portWorldWideName;

    /**
     * The current operating speed of the adapter in bits per second
     *
     * Hint: got 16 in my tests, so it's probably GBit/s?!
     *
     * @var int (long)
     */
    public $speed;
}
