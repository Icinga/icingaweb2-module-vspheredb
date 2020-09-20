<?php

namespace Icinga\Module\Vspheredb\PerformanceData\InfluxDb;

use InvalidArgumentException;

abstract class Escape
{
    const ESCAPE_COMMA_SPACE = ' ,\\';

    const ESCAPE_COMMA_EQUAL_SPACE = ' =,\\';

    const ESCAPE_DOUBLE_QUOTES = '"\\';

    const NULL = 'null';

    const TRUE = 'true';

    const FALSE = 'false';

    public static function measurement($value)
    {
        static::assertNoNewline($value);
        return \addcslashes($value, self::ESCAPE_COMMA_SPACE);
    }

    public static function key($value)
    {
        static::assertNoNewline($value);
        return \addcslashes($value, self::ESCAPE_COMMA_EQUAL_SPACE);
    }

    public static function tagValue($value)
    {
        static::assertNoNewline($value);
        return \addcslashes($value, self::ESCAPE_COMMA_EQUAL_SPACE);
    }

    public static function fieldValue($value)
    {
        // Faster checks first
        if (\is_int($value) || \ctype_digit($value) || \preg_match('/^-\d+$/', $value)) {
            return "${value}i";
        } elseif (\is_bool($value)) {
            return $value ? self::TRUE : self::FALSE;
        } elseif (\is_null($value)) {
            return self::NULL;
        } else {
            static::assertNoNewline($value);
            return '"' . \addcslashes($value, self::ESCAPE_DOUBLE_QUOTES) . '"';
        }
    }

    protected static function assertNoNewline($value)
    {
        if (\strpos($value, "\n") !== false) {
            throw new InvalidArgumentException('Newlines are forbidden in InfluxDB line protocol');
        }
    }
}
