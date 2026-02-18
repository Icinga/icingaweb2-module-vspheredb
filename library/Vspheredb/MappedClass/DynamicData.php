<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class DynamicData
{
    /** @var DynamicProperty[]|null */
    public $dynamicProperty;

    /** @var string|null */
    public $dynamicType;
}
