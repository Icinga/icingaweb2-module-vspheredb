<?php

namespace Icinga\Module\Vspheredb\ManagedObject;

use Icinga\Module\Vspheredb\Api;

class Folder extends ManagedObject
{
    public static function getDefaultPropertySet()
    {
        return array(
            'name',       // string
            'parent',     // ManagedObjectReference:ManagedEntity (rootFolder -> Unset
            'childEntity' // ManagedObjectReference:ManagedEntity[]
        );
    }

    public static function defaultSpecSet(Api $api)
    {
        return array(
            'propSet' => array(
                array(
                    'type' => 'Folder',
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
}
