<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Widget;

use ipl\Html\BaseHtmlElement;

class SubTitle extends BaseHtmlElement
{
    protected $tag = 'h2';

    /**
     * SubTitle constructor.
     * @param string $title
     * @param string|null $icon
     */
    public function __construct($title, $icon = null)
    {
        $this->setContent($title);
        if ($icon !== null) {
            $this->addAttributes(['class' => "icon-$icon"]);
        }
    }
}
