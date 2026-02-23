<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Monitoring;

interface CheckResultInterface
{
    public function getState(): CheckPluginState;

    public function getOutput(): string;
}
