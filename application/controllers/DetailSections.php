<?php

namespace Icinga\Module\Vspheredb\Controllers;

use ipl\Html\Html;
use ipl\Html\HtmlString;

trait DetailSections
{
    protected function section($content)
    {
        $content = Html::wantHtml($content)->render();
        if (\strlen($content) === 0) {
            return null;
        }

        return Html::tag('div', [
            'class' => 'section',
        ], new HtmlString($content));
    }

    protected function addSection($content)
    {
        $this->content()->add($this->section($content));

        return $this;
    }

    protected function addSections(array $sections)
    {
        foreach ($sections as $section) {
            $this->addSection($section);
        }

        return $this;
    }
}
