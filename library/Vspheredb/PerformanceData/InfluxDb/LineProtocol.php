<?php

namespace Icinga\Module\Vspheredb\PerformanceData\InfluxDb;

use InvalidArgumentException;

abstract class LineProtocol
{
    const ESCAPE_COMMA_SPACE = ' ,\\';

    const ESCAPE_COMMA_EQUAL_SPACE = ' =,\\';

    const ESCAPE_DOUBLE_QUOTES = '"\\';

    const NULL = 'null';

    const TRUE = 'true';

    const FALSE = 'false';

    public static function renderMeasurement($measurement, $tags = [], $fields = [], $timestamp = null)
    {
        return static::escapeMeasurement($measurement)
            . static::renderTags($tags)
            . static::renderFields($fields)
            . static::renderTimeStamp($timestamp)
            . "\n";
    }

    public static function renderTags($tags)
    {
        \ksort($tags);
        $string = '';
        foreach ($tags as $key => $value) {
            if (\strlen($value) === 0) {
                continue;
            }
            $string .= ',' . static::renderTag($key, $value);
        }

        return $string;
    }

    public static function renderFields($fields)
    {
        $string = '';
        foreach ($fields as $key => $value) {
            $string .= ',' . static::renderField($key, $value);
        }
        $string[0] = ' ';

        return $string;
    }

    public static function renderTimeStamp($timestamp)
    {
        if ($timestamp === null) {
            return '';
        } else {
            return " $timestamp";
        }
    }

    public static function renderTag($key, $value)
    {
        return static::escapeKey($key) . '=' . static::escapeTagValue($value);
    }

    public static function renderField($key, $value)
    {
        // Faster checks first
        if (\is_int($value) || \ctype_digit($value) || \preg_match('/^-\d+$/', $value)) {
            $value = "${value}i";
        } elseif (\is_bool($value)) {
            $value = $value ? self::TRUE : self::FALSE;
        } elseif (\is_null($value)) {
            $value = self::NULL;
        } else {
            $value = '"' . static::escapeFieldValue($value) . '"';
        }

        return static::escapeKey($key) . "=$value";
    }

    public static function escapeMeasurement($value)
    {
        static::assertNoNewline($value);
        return \addcslashes($value, self::ESCAPE_COMMA_SPACE);
    }

    public static function escapeKey($value)
    {
        static::assertNoNewline($value);
        return \addcslashes($value, self::ESCAPE_COMMA_EQUAL_SPACE);
    }

    public static function escapeTagValue($value)
    {
        static::assertNoNewline($value);
        return \addcslashes($value, self::ESCAPE_COMMA_EQUAL_SPACE);
    }

    public static function escapeFieldValue($value)
    {
        static::assertNoNewline($value);
        return \addcslashes($value, self::ESCAPE_DOUBLE_QUOTES);
    }

    protected static function assertNoNewline($value)
    {
        if (\strpos($value, "\n") !== false) {
            throw new InvalidArgumentException('Newlines are forbidden in InfluxDB line protocol');
        }
    }
}
