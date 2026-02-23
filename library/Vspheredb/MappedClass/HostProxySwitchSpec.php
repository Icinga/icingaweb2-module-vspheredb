<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object type describes the HostProxySwitch specification representing
 * the properties on a HostProxySwitch that can be configured once the object exists
 */
#[\AllowDynamicProperties]
class HostProxySwitchSpec
{
    /**
     * Type Description
     *
     * @var DistributedVirtualSwitchHostMemberBacking
     */
    public $backing;
}
