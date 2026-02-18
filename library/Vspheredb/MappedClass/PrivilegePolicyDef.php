<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class PrivilegePolicyDef
{
    /** @var string */
    public $createPrivilege;

    /** @var string */
    public $deletePrivilege;

    /** @var string */
    public $readPrivilege;

    /** @var string */
    public $updatePrivilege;
}
