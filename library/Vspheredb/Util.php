<?php

namespace Icinga\Module\Vspheredb;

use DateTime;
use Icinga\Application\Icinga;
use Icinga\Util\Csp;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Style;
use Ramsey\Uuid\Uuid;

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

        return (int)round(1000 * ((int)$time[1] + (float)$time[0]));
    }

    public static function timeStringToUnixTime($string)
    {
        return (new DateTime($string))->getTimestamp();
    }

    public static function timeStringToUnixMs($string)
    {
        $time = new DateTime($string);

        return (int)(1000 * $time->format('U.u'));
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

    public static function niceUuid(string $binaryString): string
    {
        return Uuid::fromBytes($binaryString)->toString();
    }

    public static function uuidParams(string $binaryString): array
    {
        return ['uuid' => static::niceUuid($binaryString)];
    }

    public static function uniqueClassName($prefix = ''): string
    {
        $parts = [
            $prefix,
            str_replace('=', 'A', base64_encode(uniqid('', true)))
        ];

        return implode('-', $parts);
    }

    public static function addCSPValidStyleToElement(string $className, array $style, BaseHtmlElement $element): void
    {
        $styleCsp = new Style();
        $styleCsp->setNonce(Csp::getStyleNonce());
        $class = Util::uniqueClassName($className);
        $styleCsp->add(".$class", $style);

        $element->getAttributes()->add('class', $class);
        Icinga::app()->getFrontController()->getResponse()->appendBody($styleCsp->render());
    }
}
