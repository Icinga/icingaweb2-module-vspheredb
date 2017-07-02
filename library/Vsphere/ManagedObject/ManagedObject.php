<?php

namespace Icinga\Module\Vsphere\ManagedObject;

use Icinga\Module\Vsphere\Api;

abstract class ManagedObject
{
    public static function fetchWithDefaults(Api $api)
    {
        return $api->collectProperties(static::defaultSpecSet($api));
    }

    public static function defaultSpecSet(Api $api)
    {
        return array(
            'propSet'   => static::defaultPropSet(),
            'objectSet' => static::objectSet($api->getServiceInstance()->rootFolder)
        );
    }

    protected static function defaultPropSet()
    {
        return array(
            array(
                'type'    => static::getType(),
                'all'     => 0,
                'pathSet' => static::getDefaultPropertySet()
            ),
        );
    }
}
