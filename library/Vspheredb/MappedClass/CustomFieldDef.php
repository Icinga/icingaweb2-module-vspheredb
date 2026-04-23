<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

class CustomFieldDef extends DynamicData
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
