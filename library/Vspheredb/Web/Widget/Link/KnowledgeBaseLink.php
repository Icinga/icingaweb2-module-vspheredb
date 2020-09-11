<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Link;

use gipfl\Translation\TranslationHelper;
use ipl\Html\BaseHtmlElement;

class KnowledgeBaseLink extends BaseHtmlElement
{
    use TranslationHelper;

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
