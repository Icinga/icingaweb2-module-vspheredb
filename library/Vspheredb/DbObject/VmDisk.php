<?php

namespace Icinga\Module\Vspheredb\DbObject;

use Icinga\Module\Vspheredb\Util;

class VmDisk extends BaseVmHardwareDbObject
{
    protected $table = 'vm_disk';

    protected $defaultProperties = [
        'vm_uuid'          => null,
        'hardware_key'     => null,
        'disk_uuid'        => null,
        'datastore_uuid'   => null,
        'file_name'        => null,
        'capacity'         => null,
        'disk_mode'        => null,
        'split'            => null,
        'write_through'    => null,
        'thin_provisioned' => null,
        'vcenter_uuid'     => null,
    ];

    protected $objectReferences = [
        'datastore_uuid',
    ];

    protected $booleanProperties = [
        'split',
        'write_through',
        'thin_provisioned',
    ];

    protected $propertyMap = [
        // 'backing.contentId' => 'content_id', // to binary, b82d1a823ecedaeece267061396dac9f
        'backing.uuid'        => 'disk_uuid', // to binary, 6000C299-5ba6-c2cf-1706-3ba11a5d1df0
        'backing.datastore._' => 'datastore_uuid', // make binary unique
        'backing.fileName'    => 'file_name', // transform, remove DS name? [LX_ESX_V7000_RZ1_15] tst-kjm-01.lxsbx.ka.de.dm-drogeriemarkt.com/tst-kjm-01.lxsbx.ka.de.dm-drogeriemarkt.com.vmdk
        'capacityInBytes'     => 'capacity',
        'backing.diskMode'    => 'disk_mode',
        'split'               => 'split',
        'writeThrough'        => 'write_through',
        'thinProvisioned'     => 'thin_provisioned'
    ];

    public function setDisk_uuid($value)
    {
        if (strlen($value) > 16) {
            $value = Util::uuidToBin($value);
        }

        return parent::reallySet('disk_uuid', $value);
    }
}
