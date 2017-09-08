<?php

namespace Icinga\Module\Vspheredb\SelectSet;

use SoapVar;

abstract class SelectSet
{
    /**
     * @return array
     */
    abstract public function toArray();

    public static function makeSelectionSet($name)
    {
        return new SoapVar(
            array('name' => $name),
            SOAP_ENC_OBJECT,
            null,
            null,
            'selectSet',
            null
        );
    }
}
