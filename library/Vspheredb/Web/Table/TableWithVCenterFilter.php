<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Table;

use Icinga\Module\Vspheredb\DbObject\VCenter;

interface TableWithVCenterFilter
{
    public function filterVCenter(VCenter $vCenter);
    public function filterVCenterUuids(array $uuids);
}
