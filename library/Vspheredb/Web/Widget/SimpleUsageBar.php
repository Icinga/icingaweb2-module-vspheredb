<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Compat\StyleWithNonce;

class SimpleUsageBar extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'simple-usage-bar disk-usage compact',
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
        $usedPercent = $this->used / $this->total;

        $bar = Html::tag('span', [
            'href' => '#',
            'class' => 'simple-usage-bar-bar',
            'title' => $this->title
        ]);

        $style = (new StyleWithNonce())
            ->setModule('vspheredb')
            ->addFor($bar, ['width' => sprintf('%0.3F%%', $usedPercent * 100)]);

        $this->add([$bar, $style]);
    }
}
