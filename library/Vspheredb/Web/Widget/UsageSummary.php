<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class UsageSummary extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'usage-summary-widget'
    ];

    public function __construct(ResourceUsage $usate)
    {
        $attr = ['class' => 'usage-detail'];
        $attrBox =  ['class' => 'usage-dashlet'];
        $this->add(Html::tag('div', ['style' => 'width: 100%'], [
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(Format::mhz($usate->usedMhz))),
                new CpuUsage($usate->usedMhz, $usate->totalMhz),
            ]),
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(Format::mBytes($usate->usedMb))),
                new MemoryUsage($usate->usedMb, $usate->totalMb),
            ]),
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(
                    Format::mBytes(($usate->dsCapacity - $usate->dsFreeSpace) / (1024 * 1024))
                )),
                new MemoryUsage(
                    ($usate->dsCapacity - $usate->dsFreeSpace) / (1024 * 1024),
                    $usate->dsCapacity / (1024 * 1024)
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
