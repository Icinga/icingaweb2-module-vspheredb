<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

interface PerformanceSet
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getObjectType();

    /**
     * @return string
     */
    public function getCountersGroup();

    /**
     * @return string[]
     */
    public function getCounters();
}
