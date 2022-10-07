<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * #[AllowDynamicProperties]
 */
abstract class Fault
{
    abstract public function getMessage();
}
