<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

/**
 * Non-localized key/value pair
 */
#[AllowDynamicProperties]
class KeyValue
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $value;
}
