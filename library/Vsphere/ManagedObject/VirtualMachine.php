<?php

namespace Icinga\Module\Vsphere\ManagedObject;

use Icinga\Module\Vsphere\Api;
use SoapVar;

class VirtualMachine extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'configStatus',
            'name',
            'guest.ipAddress',
            'guest.guestState',
            'guest.guestId',
            'guest.guestFullName',
            'guest.guestState',
            'guest.toolsRunningStatus',
            'runtime.powerState',
            'config.hardware.numCPU',
            'config.hardware.memoryMB',
            'config.uuid',
            'parent'
        );
    }

    public static function defaultSpecSet(Api $api)
    {
        return array(
            'propSet' => array(
                array(
                    'type' => 'VirtualMachine',
                    'all' => 0,
                    'pathSet' => static::getDefaultPropertySet()
                ),
            ),
            'objectSet' => array(
                'obj' => $api->getServiceInstance()->rootFolder,
                'skip' => false,
                'selectSet' => array(
                    static::getFolderTraversalSpec(),
                    static::getDataCenterVmTraversalSpec(),
                ),
            )
        );
    }

    public static function getFolderTraversalSpec()
    {
        $selectSetFolder = new SoapVar(
            array('name' => 'FolderTraversalSpec'),
            SOAP_ENC_OBJECT,
            null,
            null,
            'selectSet',
            null
        );

        $selectSetDataCenterVm = new SoapVar(
            array('name' => 'DataCenterVMTraversalSpec'),
            SOAP_ENC_OBJECT,
            null,
            null,
            'selectSet',
            null
        );
        $folderTraversalSpec = array(
            'name' => 'FolderTraversalSpec',
            'type' => 'Folder',
            'path' => 'childEntity',
            'skip' => false,
            $selectSetFolder,
            $selectSetDataCenterVm
        );

        return new SoapVar($folderTraversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }

    public static function getDataCenterVmTraversalSpec()
    {
        $selectSet = new SoapVar(
            array('name' => 'FolderTraversalSpec'),
            SOAP_ENC_OBJECT,
            null,
            null,
            'selectSet',
            null
        );

        $traversalSpec = array(
            'name' => 'DataCenterVMTraversalSpec',
            'type' => 'Datacenter',
            'path' => 'vmFolder',
            'skip' => false,
            $selectSet
        );

        return new SoapVar($traversalSpec, SOAP_ENC_OBJECT, 'TraversalSpec');
    }
}
