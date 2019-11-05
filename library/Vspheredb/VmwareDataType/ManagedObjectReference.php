<?php

namespace Icinga\Module\Vspheredb\VmwareDataType;

class ManagedObjectReference
{
    public $_;
    public $type;

    public function __construct($type, $moref)
    {
        $this->_ = $moref;
        $this->type = $type;
    }

    public function __toString()
    {
        if (true) {
            throw new \Exception('Moref to string!');
        }

        return $this->_;
    }
}
