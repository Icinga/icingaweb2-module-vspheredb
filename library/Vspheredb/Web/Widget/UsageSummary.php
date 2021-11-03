<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class UsageSummary extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'usage-summary-widget'
    ];

    public function __construct($perf)
    {
        $attr = ['class' => 'usage-detail'];
        $attrBox =  ['class' => 'usage-dashlet'];
        $this->add(Html::tag('div', ['style' => 'width: 100%'], [
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(Format::mhz($perf->used_mhz))),
                new CpuUsage($perf->used_mhz, $perf->total_mhz),
            ]),
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(Format::mBytes($perf->used_mb))),
                new MemoryUsage($perf->used_mb, $perf->total_mb),
            ]),
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(
                    Format::mBytes(($perf->ds_capacity - $perf->ds_free_space) / (1024 * 1024))
                )),
                new MemoryUsage(
                    ($perf->ds_capacity - $perf->ds_free_space) / (1024 * 1024),
                    $perf->ds_capacity / (1024 * 1024)
                )
            ]),
        ]));
    }

    protected function smallUnit($string)
    {
        $parts = explode(' ', $string, 2);
        return [
            $parts[0],
            Html::tag('span', ['class' => 'unit'], $parts[1])
        ];
    }

}
