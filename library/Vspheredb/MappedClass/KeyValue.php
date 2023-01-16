<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * Non-localized key/value pair
 */
#[\AllowDynamicProperties]
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
