<?php

namespace Icinga\Module\Vspheredb;

use DateTime;

class Util
{
    /**
     * Current Unix Timestamp as milliseconds since epoch
     *
     * @return int
     */
    public static function currentTimestamp()
    {
        $time = explode(' ', microtime());

        return (int) round(1000 * ((int) $time[1] + (float) $time[0]));
    }

    public static function timeStringToUnixTime($string)
    {
        return (new DateTime($string))->getTimestamp();
    }

    public static function timeStringToUnixMs($string)
    {
        $time = new DateTime($string);

        return (int) (1000 * $time->format('U.u'));
    }

    /**
     * DateTime for SOAP call
     * @param $timestamp
     * @return string
     */
    public static function makeDateTime($timestamp)
    {
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }
}
