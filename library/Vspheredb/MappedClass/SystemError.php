<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class SystemError extends Fault
{
    public $reason;

    public function getMessage()
    {
        return $this->reason;
    }
}
