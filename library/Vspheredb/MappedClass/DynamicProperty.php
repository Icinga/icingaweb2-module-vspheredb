<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class DynamicProperty
{
    /** @var string */
    public $name;

    /** @var mixed */
    public $val;
}
