<?php

namespace Icinga\Module\Vspheredb;

class Format
{
    public static function bytes($value)
    {
        $base = 1024;
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        if ($value == 0) {
            return sprintf('%.3G %s', 0, $units[0]);
        }

        $sign = '';
        if ($value < 0) {
            $value = abs($value);
            $sign = '-';
        }

        $pow = floor(log($value, $base));
        $result = $value / pow($base, $pow);
        return sprintf('%s%.3G %s', $sign, $result, $units[$pow]);
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
