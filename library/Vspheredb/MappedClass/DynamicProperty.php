<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
class DynamicProperty
{
    /** @var string */
    public $name;

    /** @var mixed */
    public $val;
}
