<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Util\Format as WebFormat;

class Format
{
    public static function bytes($bytes)
    {
        return WebFormat::bytes($bytes);
    }

    public static function mBytes($mb)
    {
        return WebFormat::bytes($mb * 1024 * 1024);
    }

    public static function mhz($mhz)
    {
        if ($mhz > 1000000) {
            return sprintf('%.2f THz', $mhz / 1000000);
        } elseif ($mhz > 1000) {
            return sprintf('%.2f GHz', $mhz / 1000);
        } else {
            return sprintf('%.2f MHz', $mhz);
        }
    }
}
