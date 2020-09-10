<?php

namespace Icinga\Module\Vspheredb\Controllers;

use ipl\Html\Html;

trait DetailSections
{
    protected function section($content)
    {
        return Html::tag('div', [
            'class' => 'section',
        ], $content);
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
