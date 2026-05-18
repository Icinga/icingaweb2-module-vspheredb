<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
abstract class EntityEventArgument
{
    /** @var string */
    public $name;
}
