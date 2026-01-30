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
        return match ($value) {
            true    => 'y',
            false   => 'n',
            null    => null,
            default => throw new InvalidArgumentException(sprintf('Boolean expected, got %s', var_export($value, 1)))
        };
    }

    /**
     * @param string|null $value
     *
     * @return bool|null
     */
    public static function dbToBoolean(?string $value): ?bool
    {
        return match ($value) {
            'y'     => true,
            'n'     => false,
            null    => null,
            default => throw new InvalidArgumentException(sprintf('Boolean expected, got %s', var_export($value, 1)))
        };
    }
}
