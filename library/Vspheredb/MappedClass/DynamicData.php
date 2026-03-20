<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class DynamicData
{
    /** @var ?DynamicProperty[] */
    public $dynamicProperty;

    /** @var ?string */
    public $dynamicType;
}
