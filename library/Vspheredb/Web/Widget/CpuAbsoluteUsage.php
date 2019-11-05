<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use gipfl\Translation\TranslationHelper;

class CpuAbsoluteUsage extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'cpu'
    ];

    public function __construct($mhz, $cores = null, $perCore = 2000)
    {
        $class = null;
        if ($cores !== null) {
            if (false) {
                $this->add(Html::tag('span', [
                    'class' => 'cpu-count'
                ], sprintf($this->translate('%d CPUs'), $cores)));
            }
            $usedPerCore = $mhz / $cores;
            if ($usedPerCore / $perCore > 0.7) {
                $class = 'critical';
            } elseif ($usedPerCore / $perCore > 0.5) {
                $class = 'warning';
            }
        }

        if ($class !== null) {
            $this->addAttributes(['class' => $class]);
        }
        if ($mhz > 1000000) {
            $unit = 'THz';
            $value = $mhz / 1000000;
        } elseif ($mhz > 1000) {
            $unit = 'GHz';
            $value = $mhz / 1000;
        } else {
            $unit = 'MHz';
            $value = $mhz;
        }
        $this->add([
            Html::tag('span', [
                'class' => 'cpu-consumption'
            ], sprintf('%.3G', $value)),
            Html::tag('span', [
                'class' => 'cpu-unit'
            ], $unit),
        ])->setSeparator("\n");
    }
}
