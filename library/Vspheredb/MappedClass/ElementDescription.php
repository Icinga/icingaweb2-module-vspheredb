<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class ElementDescription
{
    /** @var string */
    public $key;

    /** @var string */
    public $label;

    /** @var string */
    public $summary;
}
