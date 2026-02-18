<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class Tag
{
    /** @var string The tag key in human readable form */
    public $key;
}
