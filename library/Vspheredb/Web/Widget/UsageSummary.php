<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class UsageSummary extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'usage-summary-widget'
    ];

    public function __construct(ResourceUsage $usate)
    {
        $attr = ['class' => 'usage-detail'];
        $attrBox =  ['class' => 'usage-dashlet'];
        $mb = 1024 * 1024;
        $this->add(Html::tag('div', ['style' => 'width: 100%'], [
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(Format::mhz($usate->usedMhz))),
                Html::tag('span', $this->translate('Total') . ': ' . Format::mhz($usate->totalMhz)),
                (new CpuUsage($usate->usedMhz, $usate->totalMhz))->showLabels(false),
                $this->translate('CPU'),
            ]),
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(Format::mBytes($usate->usedMb))),
                Html::tag('span', $this->translate('Total') . ': ' . Format::mBytes($usate->totalMb)),
                (new MemoryUsage($usate->usedMb, $usate->totalMb))->showLabels(false),
                $this->translate('Memory'),
            ]),
            Html::tag('div', $attrBox, [
                Html::tag('div', $attr, $this->smallUnit(
                    Format::mBytes(($usate->dsCapacity - $usate->dsFreeSpace) / $mb)
                )),
                Html::tag(
                    'span',
                    $this->translate('Total') . ': ' . Format::mBytes($usate->dsCapacity / $mb)
                ),
                (new MemoryUsage(
                    ($usate->dsCapacity - $usate->dsFreeSpace) / $mb,
                    $usate->dsCapacity / $mb
                ))->showLabels(false),
                $this->translate('Storage')
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
