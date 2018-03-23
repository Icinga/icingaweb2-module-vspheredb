<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use dipl\Html\BaseElement;
use dipl\Html\Html;
use dipl\Translation\TranslationHelper;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Sync\VcenterSyncState;

class VCenterSyncInfo extends BaseElement
{
    use TranslationHelper;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'health'];

    /** @var VCenter */
    protected $vCenter;

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
            $this->vCenter->getFirstServer()->get('host')
        ));
        $this->add($title);
        if ($sync->isAlive()) {
            $this->attributes()->add('class', 'green');
            $this->add(Html::sprintf(
                $this->translate('Sync is running as PID %s by user %s on %s, last refresh happened %s'),
                (int) $syncInfo->pid,
                $syncInfo->username,
                $syncInfo->fqdn,
                $this->timeAgo($syncInfo->ts_last_refresh / 1000)
            ));
        } elseif ($syncInfo) {
            $this->attributes()->add('class', 'red');
            $this->add(Html::sprintf(
                $this->translate('Sync is not running. Last refresh occured %s by %s on %s'),
                $this->timeAgo($syncInfo->ts_last_refresh / 1000),
                $syncInfo->username,
                $syncInfo->fqdn
            ));
        } else {
            $this->attributes()->add('class', 'red');
            $this->add($this->translate('Sync has never been running'));
        }
    }

    protected function healthDiv($state, $content = null)
    {
        return Html::tag('div', ['class' => ['health', $state]], $content);
    }

    protected function timeAgo($time)
    {
        return Html::tag('span', [
            'class' => 'time-ago',
            'title' => DateFormatter::formatDateTime($time)
        ], DateFormatter::timeAgo($time));
    }
}