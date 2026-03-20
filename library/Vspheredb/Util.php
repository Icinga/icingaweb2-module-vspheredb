<?php

namespace Icinga\Module\Vspheredb;

use DateTime;
use Ramsey\Uuid\Uuid;

class Util
{
    /**
     * Current Unix Timestamp as milliseconds since epoch
     *
     * @return int
     */
    public static function currentTimestamp(): int
    {
        $time = explode(' ', microtime());

        return (int) round(1000 * ((int) $time[1] + (float) $time[0]));
    }

    public static function timeStringToUnixTime(string $string): int
    {
        return (new DateTime($string))->getTimestamp();
    }

    public static function timeStringToUnixMs(string $string): int
    {
        return (int) (1000 * (new DateTime($string))->format('U.u'));
    }

    /**
     * DateTime for SOAP call
     *
     * @param int $timestamp
     *
     * @return string
     */
    public static function makeDateTime(int $timestamp): string
    {
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    public static function niceUuid(string $binaryString): string
    {
        return Uuid::fromBytes($binaryString)->toString();
    }

    public static function uuidParams(string $binaryString): array
    {
        return ['uuid' => static::niceUuid($binaryString)];
    }
}
