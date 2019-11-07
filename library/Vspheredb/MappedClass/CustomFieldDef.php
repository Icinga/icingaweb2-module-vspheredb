<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class CustomFieldDef
{
    /** @var PrivilegePolicyDef */
    public $fieldDefPrivileges;

    /** @var PrivilegePolicyDef */
    public $fieldInstancePrivileges;

    /** @var int */
    public $key;

    /** @var string */
    public $managedObjectType;

    /** @var string */
    public $name;

    /** @var string */
    public $type;
}
