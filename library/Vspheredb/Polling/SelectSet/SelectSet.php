<?php

namespace Icinga\Module\Vspheredb\Polling\SelectSet;

use Icinga\Module\Vspheredb\MappedClass\SelectionSpec;

interface SelectSet
{
    /**
     * @return SelectionSpec[]
     */
    public static function create();
}