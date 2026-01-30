<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;

class SubTitle extends BaseHtmlElement
{
    protected $tag = 'h2';

    /**
     * SubTitle constructor.
     *
     * @param string      $title
     * @param string|null $icon
     */
    public function __construct(string $title, ?string $icon = null)
    {
        $this->setContent($title);
        if ($icon !== null) {
            $this->addAttributes(Attributes::create(['class' => "icon-$icon"]));
        }
    }
}
