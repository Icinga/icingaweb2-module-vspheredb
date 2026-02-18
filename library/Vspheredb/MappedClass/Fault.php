<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
abstract class Fault
{
    abstract public function getMessage();
}
