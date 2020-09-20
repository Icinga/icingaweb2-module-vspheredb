<?php

namespace Icinga\Module\Vspheredb\PerformanceData\InfluxDb;

abstract class LineProtocol
{
    public static function renderMeasurement($measurement, $tags = [], $fields = [], $timestamp = null)
    {
        return Escape::measurement($measurement)
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
        return Escape::key($key) . '=' . Escape::tagValue($value);
    }

    public static function renderField($key, $value)
    {

        return Escape::key($key) . '=' . Escape::fieldValue($value);
    }
}
