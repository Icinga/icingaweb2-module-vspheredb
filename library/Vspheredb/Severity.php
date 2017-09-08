<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\ProgrammingError;

class Severity
{
    protected static $colorToStateMap = array(
        'green'  => 'Normal',
        'yellow' => 'Warning',
        'red'    => 'Alert',
    );

    protected static $stateToColorMap = array(
        'Normal'  => 'green',
        'Warning' => 'yellow',
        'Alert'   => 'red',
    );

    public static function colorToSeverity($color)
    {
        if (array_key_exists($color, self::$colorToStateMap)) {
            return self::$colorToStateMap[$color];
        } else {
            throw new ProgrammingError('Color expected, got "%s"', $color);
        }
    }

    public static function severityToColor($severity)
    {
        if (array_key_exists($severity, self::$stateToColorMap)) {
            return self::$stateToColorMap[$severity];
        } else {
            throw new ProgrammingError('Severity expected, got "%s"', $severity);
        }
    }
}
