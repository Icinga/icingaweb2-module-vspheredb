<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class LocalizedMethodFault
{
    /** @var MethodFault */
    public $fault;

    /** @var string|null Servers are required to send the localized message, clients are not */
    public $localizedMessage;
}
