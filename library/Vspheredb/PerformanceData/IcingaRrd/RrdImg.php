<?php

namespace Icinga\Module\Vspheredb\PerformanceData\IcingaRrd;

use ipl\Html\Html;
use gipfl\IcingaWeb2\Img;

class RrdImg
{
    public static function vmIfTraffic($moref, $device)
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Throughput (bits/s, %s RX / %s TX)'),
            static::colorLegend('#57985B'),
            static::colorLegend('#0095BF')
        ), $moref, "iface$device", 'vSphereDB-vmIfTraffic');
    }

    public static function vmIfPackets($moref, $device)
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Packets (%s / %s Unicast, %s BCast, %s MCast, %s Dropped)'),
            static::colorLegend('#57985B'),
            static::colorLegend('#0095BF'),
            static::colorLegend('#EE55FF'),
            static::colorLegend('#FF9933'),
            static::colorLegend('#FF5555')
        ), $moref, "iface$device", 'vSphereDB-vmIfPackets');
    }

    public static function vmDiskSeeks($moref, $device)
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Disk Seeks: %s small / %s medium / %s large'),
            static::colorLegend('#57985B'),
            static::colorLegend('#FFED58'),
            static::colorLegend('#FFBF58')
        ), $moref, "disk$device", 'vSphereDB-vmDiskSeeks');
    }

    public static function vmDiskReadWrites($moref, $device)
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Average Number %s Reads / %s Writes'),
            static::colorLegend('#57985B'),
            static::colorLegend('#0095BF')
        ), $moref, "disk$device", 'vSphereDB-vmDiskReadWrites');
    }

    public static function vmDiskTotalLatency($moref, $device)
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Latency %s Read / %s Write'),
            static::colorLegend('#57985B'),
            static::colorLegend('#0095BF')
        ), $moref, "disk$device", 'vSphereDB-vmDiskTotalLatency');
    }

    protected static function prepareImg($moref, $device, $template)
    {
        // Disk was 300x140, Net 340x180
        $width = 340;
        $height = 180;
        $end = \floor(\time() / 300) * 300;
        $start = $end - 86400;
        $start = $end - 14400;
        $params = [
            'file'     => \sprintf('%s/%s.rrd', $moref, $device),
            'height'   => $height,
            'width'    => $width,
            'rnd'      => floor(time() / 20),
            'format'   => 'png',
            'start'    => $start,
            'end'      => $end,
        ];
        $attrs = [
            'height' => $height,
            'width'  => $width,
            'style'  => 'float: right;'
            // 'style'  => 'border-bottom: 1px solid rgba(0, 0, 0, 0.3); border-left: 1px solid rgba(0, 0, 0, 0.3);'
        ];

        return Img::create('rrd/img', $params + [
            'template' => $template,
        ], $attrs);
    }

    protected static function colorLegend($color)
    {
        return Html::tag('div', [
            'style' => "    border: 1px solid rgba(0, 0, 0, 0.3); background-color: $color;"
                . ' width: 0.8em; height: 0.8em; margin: 0.1em; display: inline-block; vertical-align: middle;'
        ]);
    }

    protected static function wrapImage($title, $moref, $device, $template)
    {
        return Html::tag('div', [
            'style' => 'display: inline-block; margin-left: 1em;' // TODO, CSS. disk was 1em, net 2em
        ], [
            Html::tag('strong', [
                'style' => 'display: block; padding-left: 3em'
            ], $title),
            static::prepareImg($moref, $device, $template),
        ]);
    }
}
