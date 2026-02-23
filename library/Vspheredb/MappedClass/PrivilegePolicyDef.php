<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
