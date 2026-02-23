<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Table;

class VsphereApiConnectionTable extends ArrayTable
{
    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('VCenter'),
            $this->translate('Server'),
            $this->translate('State'),
        ];
    }
}
