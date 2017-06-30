<?php

namespace Icinga\Module\Vsphere\ManagedObject;

use Icinga\Module\Vsphere\Api;
use SoapVar;

abstract class ManagedObject
{
    protected static function prepareFetchDefaultsRequest(Api $api)
    {
        $si = $api->getServiceInstance();
        return array(
            '_this'   => $si->propertyCollector,
            'specSet' => static::defaultSpecSet($api)
        );
    }

    public static function fetchWithDefaults(Api $api)
    {
        $result = $api->soapCall(
            'RetrieveProperties',
            static::prepareFetchDefaultsRequest($api)
        );

        return FullTraversal::makeNiceResult($result);
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
