<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\Util;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class SimpleUsageBar extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'disk-usage compact',
        'data-base-target' => '_next'
    ];

    /** @var int */
    protected $used;

    /** @var int */
    protected $total;

    /** @var string */
    protected $title;

    public function __construct($used, $total, $title)
    {
        $this->used = $used;
        $this->total = $total;
        $this->title = $title;
    }

    protected function assemble()
    {

        $usageBarElement = Html::tag('span', [
            'href' => '#',
            'title' => $this->title
        ]);

        $usedPercent = $this->used / $this->total;
        Util::addCSPValidStyleToElement(
            'simple-usage-bar',
            [
                "width" => sprintf("%0.3F%%", $usedPercent * 100),
                "display" => "block !important",
                "background-color" => "rgba(70, 128, 255, 0.75)",
                "height" => "100%"
            ],
            $usageBarElement
        );

        $this->add($usageBarElement);
    }
}
