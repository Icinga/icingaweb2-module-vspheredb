<?php

namespace Icinga\Module\Vspheredb;

class Format
{
    public static function bytes($value)
    {
        $base = 1024;
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];

        $sign = '';
        if ($value < 0) {
            $value = abs($value);
            $sign = '-';
        }

        // max() deals with $value < 1, 1000 instead of base is to fit into %.3G
        $pow = max(0, floor(log($value, 1000)));
        $result = $value / pow($base, $pow);

        // Problem: %.3G is 0.978 for $value = 1001 / 1024, but we want to see 0.98
        $output = sprintf('%.3G', $result);
        if (preg_match('/^0[,.]/', $output)) {
            $output = sprintf('%.3G', round((float) $output, 2));
        }

        return sprintf('%s%s %s', $sign, $output, $units[$pow]);
    }

    public static function mBytes($value)
    {
        return static::bytes($value * 1024 * 1024);
    }

    public static function linkSpeedMb($mb)
    {
        if ($mb >= 1000000) {
            return sprintf('%.3G TBit/s', $mb / 1000000);
        } elseif ($mb >= 1000) {
            return sprintf('%.3G GBit/s', $mb / 1000);
        } else {
            return sprintf('%.3G MBit/s', $mb);
        }
    }

    public static function mhz($mhz)
    {
        if ($mhz >= 1000000) {
            return sprintf('%.3G THz', $mhz / 1000000);
        } elseif ($mhz >= 1000) {
            return sprintf('%.3G GHz', $mhz / 1000);
        } else {
            return sprintf('%.3G MHz', $mhz);
        }
    }
}
