<?php

namespace Icinga\Module\Vspheredb\Exception;

class NotAuthenticatedException extends VmwareException
{
    /** @var ?array */
    public ?array $paths = null;
}
