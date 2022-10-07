<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
class ElementDescription
{
    /** @var string */
    public $key;

    /** @var string */
    public $label;

    /** @var string */
    public $summary;
}
