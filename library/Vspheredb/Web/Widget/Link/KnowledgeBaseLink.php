<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Widget\Link;

use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;

class KnowledgeBaseLink extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'a';

    protected $defaultAttributes = [
        'target' => '_blank',
        'class'  => 'vmware_kb_link',
    ];

    public function __construct($id, $title = null, $label = null)
    {
        $this->id = $id;
        if ($label === null) {
            $this->setContent("KB $id");
        } else {
            $this->setContent($label);
        }

        $this->setAttribute('title', $title);
        $this->setAttribute('href', 'https://kb.vmware.com/s/article/' . \rawurlencode($id));
    }
}
