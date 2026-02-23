<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
class LocalizedMethodFault
{
    /** @var MethodFault */
    public $fault;

    /** @var string|null Servers are required to send the localized message, clients are not */
    public $localizedMessage;
}
