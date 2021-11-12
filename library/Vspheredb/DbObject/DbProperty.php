<?php

namespace Icinga\Module\Vspheredb\DbObject;

use InvalidArgumentException;

class DbProperty
{
    /**
     * @param ?boolean $value
     * @return null|string
     */
    public static function booleanToDb($value)
    {
        if ($value === true) {
            return 'y';
        } elseif ($value === false) {
            return 'n';
        } elseif ($value === null) {
            return null;
        } else {
            throw new InvalidArgumentException(
                'Boolean expected, got %s',
                var_export($value, 1)
            );
        }
    }

    /**
     * @param ?string $value
     * @return bool|null
     */
    public static function dbToBoolean($value)
    {
        if ($value === 'y') {
            return true;
        } elseif ($value === 'n') {
            return false;
        } elseif ($value === null) {
            return null;
        } else {
            throw new InvalidArgumentException(
                'Boolean expected, got %s',
                var_export($value, 1)
            );
        }
    }
}
