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
    protected int $used;

    /** @var int */
    protected int $total;

    /** @var string */
    protected string $title;

    public function __construct(int $used, int $total, string $title)
    {
        $this->used = $used;
        $this->total = $total;
        $this->title = $title;
    }

    protected function assemble(): void
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
