<?php

namespace Icinga\Module\Vspheredb\Polling\PropertySet;

use Icinga\Module\Vspheredb\MappedClass\PropertySpec;

interface PropertySet
{
    /**
     * @return PropertySpec[]
     */
    public static function create();
}