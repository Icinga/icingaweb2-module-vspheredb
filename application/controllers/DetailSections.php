<?php

namespace Icinga\Module\Vspheredb\Controllers;

use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;

trait DetailSections
{
    /**
     * @param mixed $content
     *
     * @return ?HtmlElement
     */
    protected function section(mixed $content): ?HtmlElement
    {
        $content = Html::wantHtml($content)->render();
        if (strlen($content) === 0) {
            return null;
        }

        return Html::tag('div', ['class' => 'section'], new HtmlString($content));
    }

    /**
     * @param mixed $content
     *
     * @return $this
     */
    protected function addSection(mixed $content): static
    {
        $this->content()->add($this->section($content));

        return $this;
    }

    /**
     * @param array $sections
     *
     * @return $this
     */
    protected function addSections(array $sections): static
    {
        foreach ($sections as $section) {
            $this->addSection($section);
        }

        return $this;
    }
}
