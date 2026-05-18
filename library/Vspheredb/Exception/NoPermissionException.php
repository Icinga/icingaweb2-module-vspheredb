<?php

namespace Icinga\Module\Vspheredb\Exception;

class NoPermissionException extends VmwareException
{
    public ?array $paths = null;
}
