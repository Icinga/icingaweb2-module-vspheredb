<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\I18n\Translation;

class CpuAbsoluteUsage extends BaseHtmlElement
{
    use Translation;

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
        [$value, $unit] = Format::mhzWithSeparateUnit($mhz);
        $this->add([
            Html::tag('span', [
                'class' => 'cpu-consumption'
            ], $value),
            Html::tag('span', [
                'class' => 'cpu-unit'
            ], $unit),
        ])->setSeparator("\n");
    }
}
