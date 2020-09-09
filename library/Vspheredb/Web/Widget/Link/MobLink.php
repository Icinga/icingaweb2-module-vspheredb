<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Link;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class MobLink extends HtmlDocument
{
    use TranslationHelper;

    protected $vCenter;

    protected $label;

    protected $moRef;

    public function __construct(VCenter $vCenter, BaseDbObject $object, $label = null)
    {
        $this->vCenter = $vCenter;
        $this->moRef = $object->object()->get('moref');
        if ($label === null) {
            $this->label = $this->moRef;
        }
    }

    protected function assemble()
    {
        try {
            $server = $this->vCenter->getFirstServer();
            $this->add(Html::tag('a', [
                'href' => sprintf(
                    'https://%s/mob/?moid=%s',
                    $server->get('host'),
                    \rawurlencode($this->moRef)
                ),
                'target' => '_blank',
                'title' => $this->translate('Jump to the Managed Object browser')
            ], $this->label));
        } catch (NotFoundError $e) {
            $this->add([
                Icon::create('warning-empty', [
                    'class' => 'red'
                ]),
                ' ',
                $this->translate('No related vServer has been configured')
            ]);
        }
    }
}
