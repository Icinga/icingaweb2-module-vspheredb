<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This data object describes the Remote Direct Memory Access (RDMA) host bus adapter interface
 *
 * @since vSphere API 7.0
 */
class HostRdmaHba extends HostHostBusAdapter
{
    /**
     * Device name of the associated RDMA device, if any
     *
     * Should match the device property of the corresponding RDMA device
     *
     * @var string
     */
    public $associatedRdmaDevice;
}
