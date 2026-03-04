<?php

namespace Icinga\Module\Vspheredb\Exception;

class NoPermissionException extends VmwareException
{
    /** @var ?array */
    public ?array $paths = null;
}
