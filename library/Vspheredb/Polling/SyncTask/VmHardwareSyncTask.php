<?php

namespace Icinga\Module\Vspheredb\Polling\SyncTask;

use gipfl\Json\JsonString;
use Icinga\Module\Vspheredb\DbObject\VmHardware;
use Icinga\Module\Vspheredb\Polling\PropertySet\VmHardwarePropertySet;
use Icinga\Module\Vspheredb\Polling\SelectSet\VirtualMachineSelectSet;
use Icinga\Module\Vspheredb\Polling\SyncStore\VmHardwareSyncStore;

class VmHardwareSyncTask extends SyncTask
{
    protected $label = 'VM Hardware';
    protected $tableName = 'vm_hardware';
    protected $objectClass = VmHardware::class;
    protected $selectSetClass = VirtualMachineSelectSet::class;
    protected $propertySetClass = VmHardwarePropertySet::class;
    protected $syncStoreClass = VmHardwareSyncStore::class;

    public function tweakResult($result)
    {
        $whitelist = [
            // Problem with config.hardware: some properties are binary,
            // e.g. endorsementKeyCertificateSigningRequest: string[], endorsementKeyCertificate: string[]

            'key',

            // Hardware
            'deviceInfo', // .label, .summary
            'busNumber',
            'unitNumber',
            'controllerKey',

            // 'slotInfo',
            // 'connectable',

            // Nic:
            'backing',
            'macAddress',
            'addressType',

            // Disk:
            // 'backing', // exists in Nic: too
            'capacityInBytes',
            'split',
            'writeThrough',
            'thinProvisioned',
        ];
        // $unset = []; // used only when looking for new properties
        foreach ($result as $key => $value) {
            if (isset($value['config.hardware']->device)) {
                foreach ($value['config.hardware']->device as $device) {
                    foreach (array_keys((array) $device) as $k) {
                        if (! in_array($k, $whitelist)) {
                            // $unset[$k] = true;
                            unset($device->$k);
                        }
                    }
                }
            }
        }

        // echo implode(', ', array_keys($unset)) . "\n";
        // unset properties in a test environment:
        // dynamicProperty, dynamicType, device, videoRamSizeInKB, numDisplays, useAutoDetect, enable3DSupport,
        // use3dRenderer, graphicsMemorySizeInKB, slotInfo, id, allowUnrestrictedCommunication, filterEnable,
        // hotAddRemove, sharedBus, scsiCtlrUnitNumber, connectable, capacityInKB, shares, storageIOAllocation,
        // diskObjectId, nativeUnmanagedLinkedClone, wakeOnLanEnabled, resourceAllocation, uptCompatibilityEnabled,
        // autoConnectDevices, endorsementKeyCertificateSigningRequest, endorsementKeyCertificate, connected, vendor,
        // product, family, speed, ehciEnabled, yieldOnPoll
    }
}
