<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class VmReconfiguredEvent extends VmEvent
{
    /** @var VirtualMachineConfigSpec */
    public $configSpec;
}
