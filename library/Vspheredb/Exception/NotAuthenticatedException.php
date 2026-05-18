<?php

namespace Icinga\Module\Vspheredb\Exception;

class NotAuthenticatedException extends VmwareException
{
    public ?array $paths = null;
}
