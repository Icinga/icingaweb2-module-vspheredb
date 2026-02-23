<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\ProgrammingError;

class Severity
{
    protected static $colorToStateMap = [
        'green'  => 'Normal',
        'yellow' => 'Warning',
        'red'    => 'Alert',
    ];

    protected static $stateToColorMap = [
        'Normal'  => 'green',
        'Warning' => 'yellow',
        'Alert'   => 'red',
    ];

    /**
     * @param string $color
     *
     * @return string
     *
     * @throws ProgrammingError
     */
    public static function colorToSeverity(string $color): string
    {
        if (array_key_exists($color, self::$colorToStateMap)) {
            return self::$colorToStateMap[$color];
        } else {
            throw new ProgrammingError('Color expected, got "%s"', $color);
        }
    }

    /**
     * @param string $severity
     *
     * @return string
     *
     * @throws ProgrammingError
     */
    public static function severityToColor(string $severity): string
    {
        if (array_key_exists($severity, self::$stateToColorMap)) {
            return self::$stateToColorMap[$severity];
        } else {
            throw new ProgrammingError('Severity expected, got "%s"', $severity);
        }
    }
}
