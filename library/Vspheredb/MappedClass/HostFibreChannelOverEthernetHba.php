<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object type describes the FCoE host bus adapter interface
 *
 * Terminology is borrowed from T11's working draft of the Fibre Channel Backbone 5 standard (FC-BB-5).
 * The draft can be found at http://www.t11.org.
 */
class HostFibreChannelOverEthernetHba extends HostFibreChannelHba
{

    /** @var boolean True if this host bus adapter is a software based FCoE initiator */
    public $isSoftwareFcoe;

    /**
     * Link information that can be used to uniquely identify this FCoE HBA
     *
     * @var HostFibreChannelOverEthernetHbaLinkInfo
     */
    public $linkInfo;

    /** @var boolean True if this host bus adapter has been marked for removal */
    public $markedForRemoval;

    /** @var string The name associated with this FCoE HBA's underlying FcoeNic */
    public $underlyingNic;
}
