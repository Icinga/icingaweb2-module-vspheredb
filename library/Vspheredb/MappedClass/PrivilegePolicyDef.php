<?php

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
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
