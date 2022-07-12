<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * The data object type describes the Serial Attached Scsi(SAS) interface
 */
class HostSerialAttachedHba extends HostHostBusAdapter
{
    /** @var int (long) The world wide node name for the adapter */
    public $nodeWorldWideName;
}
