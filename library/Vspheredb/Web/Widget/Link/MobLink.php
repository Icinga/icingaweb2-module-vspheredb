<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Link;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

use function rawurlencode;
use function sprintf;

class MobLink extends HtmlDocument
{
    use TranslationHelper;

    protected $vCenter;

    protected $label;

    protected $moRef;

    public function __construct(VCenter $vCenter, BaseDbObject $object = null, $label = null)
    {
        $this->vCenter = $vCenter;
        if ($object) {
            $this->moRef = $object->object()->get('moref');
        }
        if ($label === null) {
            if ($this->moRef) {
                $this->label = $this->moRef;
            } else {
                $this->label = 'MOB';
            }
        } else {
            $this->label = $label;
        }
    }

    protected function assemble()
    {
        try {
            $server = $this->vCenter->getFirstServer(false);
            if ($this->moRef) {
                $this->add($this->createObjectLink($server, $this->moRef, $this->label));
            } else {
                $this->add($this->createBaseLink($server, $this->label));
            }
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

    /**
     * @param VCenterServer $server
     * @param string $moRef
     * @param string $label
     * @return \ipl\Html\BaseHtmlElement
     */
    protected function createObjectLink(VCenterServer $server, $moRef, $label)
    {
        return Html::tag('a', [
            'href' => sprintf(
                'https://%s/mob/?moid=%s',
                $server->get('host'),
                rawurlencode($moRef)
            ),
            'target' => '_blank',
            'title' => sprintf(
                $this->translate('Show "%s" in the Managed Object Browser (MOB)'),
                $moRef
            ),
            'class' => 'icon-eye',
        ], $label);
    }

    /**
     * @param VCenterServer $server
     * @param string $label
     * @return \ipl\Html\BaseHtmlElement
     */
    protected function createBaseLink(VCenterServer $server, $label)
    {
        return Html::tag('a', [
            'href' => sprintf('https://%s/mob/', $server->get('host')),
            'target' => '_blank',
            'title' => sprintf(
                $this->translate('Open the Managed Object Browser (MOB)')
            ),
            'class' => 'icon-eye',
        ], $label);
    }
}
