<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\DbObject;

class HostSystemList extends MoRefList
{
    public const LIST_TABLE_NAME = 'host_list';

    public const MEMBER_TABLE_NAME = 'host_list_member';
}
