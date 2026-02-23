<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
class DatacenterConfigInfo
{
    /**
     * Key for Default Hardware Version used on this datacenter in the format of key.
     * This field affects defaultConfigOption returned by environmentBrowser of all its
     * children with this field unset.
     *
     * @var ?string
     */
    public $defaultHardwareVersionKey;
}
