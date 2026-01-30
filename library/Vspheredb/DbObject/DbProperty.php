<?php

namespace Icinga\Module\Vspheredb\DbObject;

use InvalidArgumentException;

class DbProperty
{
    /**
     * @param bool|null $value
     *
     * @return string|null
     */
    public static function booleanToDb(?bool $value): ?string
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
     * @param string|null $value
     *
     * @return bool|null
     */
    public static function dbToBoolean(?string $value): ?bool
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
