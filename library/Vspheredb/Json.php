<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Module\Vspheredb\Exception\JsonEncodeException;

// Stolen from Director, should be moved to incubator
class Json
{
    public static function encode($mixed, $flags = null)
    {
        $result = \json_encode($mixed, $flags);

        if ($result === false && json_last_error() !== JSON_ERROR_NONE) {
            throw JsonEncodeException::forLastJsonError();
        }

        return $result;
    }

    public static function decode($string)
    {
        $result = \json_decode($string);

        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw JsonEncodeException::forLastJsonError();
        }

        return $result;
    }
}
