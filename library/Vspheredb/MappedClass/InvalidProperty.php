<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class InvalidProperty extends Fault
{
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    public function getMessage()
    {
        return sprintf('Invalid Property: "%s"', $this->getName());
    }
}
