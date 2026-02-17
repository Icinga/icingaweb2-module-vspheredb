<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Sync\VcenterSyncState;
use Icinga\Module\Vspheredb\WebUtil;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class VCenterSyncInfo extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'health'];

    /** @var VCenter */
    protected VCenter $vCenter;

    public function __construct(VCenter $vCenter)
    {
        $this->vCenter = $vCenter;
    }

    protected function assemble()
    {
        $sync = new VcenterSyncState($this->vCenter);
        $syncInfo = $sync->getInfo();

        $title = Html::tag('h1', null, sprintf(
            $this->translate('VCenter %s'),
            $this->vCenter->getFirstServer(false)->get('host')
        ));
        $this->add($title);
        if ($sync->isAlive()) {
            $this->getAttributes()->add('class', 'green');
            $this->add(Html::sprintf(
                $this->translate('Sync for %s is running as PID %s by user %s on %s, last refresh happened %s'),
                $this->getVersionInfoString(),
                (int) $syncInfo->pid,
                $syncInfo->username,
                $syncInfo->fqdn,
                WebUtil::timeAgo($syncInfo->ts_last_refresh / 1000)
            ));
        } elseif ($syncInfo) {
            $this->getAttributes()->add('class', 'red');
            $this->add(Html::sprintf(
                $this->translate('Sync is not running. Last refresh occured %s by %s on %s'),
                WebUtil::timeAgo($syncInfo->ts_last_refresh / 1000),
                $syncInfo->username,
                $syncInfo->fqdn
            ));
        } else {
            $this->getAttributes()->add('class', 'red');
            $this->add($this->translate('Sync has never been running'));
        }
    }

    protected function getVersionInfoString(): string
    {
        return sprintf(
            '%s %s build-%s',
            $this->vCenter->get('api_type'),
            $this->vCenter->get('version'),
            $this->vCenter->get('build')
        );
    }

    protected function healthDiv(string $state, $content = null): HtmlElement
    {
        return Html::tag('div', ['class' => ['health', $state]], $content);
    }
}
