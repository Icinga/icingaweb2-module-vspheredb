<?php

namespace Icinga\Module\Vspheredb\PerformanceData\IcingaRrd;

use gipfl\IcingaWeb2\Img;
use ipl\Html\FormattedString;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class RrdImg
{
    protected const COLOR_RED = 'red'; // #FF5555

    protected const COLOR_GREEN = 'green'; // #57985B

    protected const COLOR_CYAN = 'cyan'; // #0095BF

    protected const COLOR_MAGENTA = 'magenta'; // #EE55FF

    protected const COLOR_ORANGE = 'orange'; // #FF9933

    protected const COLOR_YELLOW = 'yellow'; // #FFED58

    public static function vmIfTraffic(string $moref, int $device): HtmlElement
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Throughput (bits/s, %s RX / %s TX)'),
            static::colorLegend(static::COLOR_GREEN),
            static::colorLegend(static::COLOR_CYAN)
        ), $moref, "iface$device", 'vSphereDB-vmIfTraffic');
    }

    public static function vmIfPackets(string $moref, int $device): HtmlElement
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Packets (%s / %s Unicast, %s BCast, %s MCast, %s Dropped)'),
            static::colorLegend(static::COLOR_GREEN),
            static::colorLegend(static::COLOR_CYAN),
            static::colorLegend(static::COLOR_MAGENTA),
            static::colorLegend(static::COLOR_ORANGE),
            static::colorLegend(static::COLOR_RED)
        ), $moref, "iface$device", 'vSphereDB-vmIfPackets');
    }

    public static function vmDiskSeeks(string $moref, string $device): HtmlElement
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Disk Seeks: %s small / %s medium / %s large'),
            static::colorLegend(static::COLOR_GREEN),
            static::colorLegend(static::COLOR_YELLOW),
            static::colorLegend(static::COLOR_YELLOW)
        ), $moref, "disk$device", 'vSphereDB-vmDiskSeeks');
    }

    public static function vmDiskReadWrites(string $moref, string $device): HtmlElement
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Average Number %s Reads / %s Writes'),
            static::colorLegend(static::COLOR_GREEN),
            static::colorLegend(static::COLOR_CYAN)
        ), $moref, "disk$device", 'vSphereDB-vmDiskReadWrites');
    }

    public static function vmDiskTotalLatency(string $moref, string $device): HtmlElement
    {
        return static::wrapImage(Html::sprintf(
            mt('vspheredb', 'Latency %s Read / %s Write'),
            static::colorLegend(static::COLOR_GREEN),
            static::colorLegend(static::COLOR_CYAN)
        ), $moref, "disk$device", 'vSphereDB-vmDiskTotalLatency');
    }

    protected static function prepareImg(string $moref, string $device, string $template): Img
    {
        // Disk was 300x140, Net 340x180
        $width = 340;
        $height = 180;
        $end = floor(time() / 300) * 300;
        $start = $end - 86400;
        $start = $end - 14400;
        $params = [
            'file'     => sprintf('%s/%s.rrd', $moref, $device),
            'height'   => $height,
            'width'    => $width,
            'rnd'      => floor(time() / 20),
            'format'   => 'png',
            'start'    => $start,
            'end'      => $end,
        ];

        return Img::create('rrd/img', $params + ['template' => $template], ['class' => 'rrd-image']);
    }

    protected static function colorLegend(string $color): HtmlElement
    {
        return Html::tag('div', ['class' => 'color-square color-' . $color]);
    }

    protected static function wrapImage(
        FormattedString $title,
        string $moref,
        string $device,
        string $template
    ): HtmlElement {
        // TODO, CSS. disk was 1em, net 2em
        return Html::tag('div', ['class' => 'rrd-image-legend'], [
            Html::tag('strong', $title),
            static::prepareImg($moref, $device, $template),
        ]);
    }
}
