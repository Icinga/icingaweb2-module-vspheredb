<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class PhysicalNic
{
    /**
     * If set the flag indicates if the physical network adapter supports autonegotiate
     *
     * @var boolean
     */
    public $autoNegotiateSupported;

    /**
     * The device name of the physical network adapter
     *
     * @var string
     */
    public $device;

    /**
     * The name of the driver
     *
     * @var string
     */
    public $driver;

    /**
     * The FCoE configuration of the physical network adapter
     *
     * @var FcoeConfig
     */
    public $fcoeConfiguration;

    /**
     * The linkable identifier
     *
     * @var string
     */
    public $key;

    /**
     * The current link state of the physical network adapter. If this object
     * is not set, then the link is down.
     *
     * @var PhysicalNicLinkInfo|null
     */
    public $linkSpeed;

    /**
     * The media access control (MAC) address of the physical network adapter
     *
     * @var string
     */
    public $mac;

    /**
     * Device hash of the PCI device corresponding to this physical network adapter
     *
     * @var string
     */
    public $pci;

    /**
     * Flag indicating whether the NIC allows resource pool based scheduling for
     * network I/O control
     *
     * @var boolean
     */
    public $resourcePoolSchedulerAllowed;

    /**
     * If resourcePoolSchedulerAllowed is false, this property advertises the
     * reason for disallowing resource scheduling on this NIC. The reasons may
     * be one of PhysicalNicResourcePoolSchedulerDisallowedReason
     *
     * @var string[]
     */
    public $resourcePoolSchedulerDisallowedReason;

    /**
     * The specification of the physical network adapter
     *
     * @var PhysicalNicSpec
     */
    public $spec;

    /**
     * The valid combinations of speed and duplexity for this physical network
     * adapter. The speed and the duplex settings usually must be configured as
     * a pair. This array lists all the valid combinations available for a
     * physical network adapter.
     *
     * Autonegotiate is not listed as one of the combinations supported. If is
     * implicitly supported by the physical network adapter unless
     * autoNegotiateSupported is set to false.
     *
     * @var PhysicalNicLinkInfo[]
     */
    public $validLinkSpecification;

    /**
     * Flag indicating whether the NIC supports VMDirectPath Gen 2. Note that
     * this is only an indicator of the capabilities of this NIC, not of the
     * whole host.
     *
     * If the host software is not capable of VMDirectPath Gen 2, this property
     * will be unset, as the host cannot provide information on the NIC capability.
     *
     * See vmDirectPathGen2Supported
     *
     * @var boolean
     */
    public $vmDirectPathGen2Supported;

    /**
     * If vmDirectPathGen2Supported is true, this property advertises the
     * VMDirectPath Gen 2 mode supported by this NIC (chosen from
     * PhysicalNicVmDirectPathGen2SupportedMode). A mode may require that the
     * associated vSphere Distributed Switch have a particular ProductSpec in
     * order for network passthrough to be possible.
     *
     * @var string
     */
    public $vmDirectPathGen2SupportedMode;

    /**
     * Flag indicating whether the NIC is wake-on-LAN capable
     *
     * @var boolean
     */
    public $wakeOnLanSupported;
}
