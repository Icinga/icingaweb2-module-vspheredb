<?php

namespace Icinga\Module\Vspheredb\Svg;

use dipl\Html\Html;
use dipl\Html\ValidHtml;

class SvgUtils
{
    public static function sendSvg(ValidHtml $svg)
    {
        header('Content-Type: image/svg+xml');
        $header = '<?xml version="1.0"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN"
    "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
';
        echo $header;
        echo $svg->render();
        exit;
    }

    public static function makePoints($points)
    {
        $result = [];
        foreach ($points as $point) {
            $result[] = static::float($point[0]) . ',' . static::float($point[1]);
        }

        return implode(' ', $result);
    }

    public static function adjustPoints($points, $offsetX = 0, $offsetY = 0)
    {
        foreach ($points as & $pair) {
            if ($offsetX !== 0) {
                $pair[0] += $offsetX;
            }
            if ($offsetY !== 0) {
                $pair[1] += $offsetY;
            }
        }

        return $points;
    }

    public static function rectangle($width, $height, $attributes = [])
    {
        $attributes['width'] = static::float($width);
        $attributes['height'] = static::float($height);

        return Html::tag('rect', $attributes)->setVoid();
    }

    public static function float($number)
    {
        return preg_replace('/(?:0+|\.0+)$/', '', sprintf('%.6F', $number));
    }

    public static function circle($x, $y, $radius, $attributes = [])
    {
        $attributes['cx'] = static::float($x);
        $attributes['cy'] = static::float($y);
        $attributes['r'] = static::float($radius);

        return Html::tag('circle', $attributes)->setVoid();
    }

    public static function createSvg($width, $height)
    {
        return Html::tag('svg', [
            'xmlns'  => 'http://www.w3.org/2000/svg',
            'width'  => static::float($width),
            'height' => static::float($height),
            'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
        ]);
    }
}
