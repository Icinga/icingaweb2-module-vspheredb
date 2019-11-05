<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class MissingProperty
{
    /** @var SytemError|SecurityError These are the known allowed LocalizedMethodFault types */
    public $fault;

    /** @var string */
    public $path;

    public function isSecurityError()
    {
        return $this->fault instanceof SecurityError;
    }

    public function isNotAuthenticated()
    {
        return $this->fault->fault instanceof NotAuthenticated;
    }

    public function isNoPermission()
    {
        return $this->fault->fault instanceof NoPermission;
    }
}
