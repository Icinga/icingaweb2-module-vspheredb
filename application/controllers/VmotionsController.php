<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Table\VMotionHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\EventHeatmapCalendars;
use Icinga\Module\Vspheredb\Web\Widget\VMotionHeatmap;

class VmotionsController extends Controller
{
    public function init()
    {
        $this->handleTabs();
    }

    public function indexAction()
    {
        $day = $this->params->shift('day');

        $table = new VMotionHistoryTable($this->db());
        $dayStamp = strtotime($day);
        if ($day) {
            $this->addTitle('VMotion History on %s', DateFormatter::formatDate($dayStamp));
            $table->getQuery()->where(
                'ts_event_ms >= ?',
                strtotime("$day 00:00:00") * 1000
            )->where(
                'ts_event_ms <= ?',
                (strtotime("$day 23:59:59") + 1) * 1000
            );
        } else {
            $this->addTitle('VMotion History');
        }
        $table->renderTo($this);
    }

    public function heatmapAction()
    {
        $this->addTitle('VMotion Heatmap');
        $this->content()->add(VMotionHeatmap::create($this->vCenter(), 'vspheredb/vmotions'));
    }

    protected function handleTabs()
    {
        $tabs = $this->tabs()->add('index', [
            'label' => $this->translate('VMotion History'),
            'url' => 'vspheredb/vmotions'
        ]);

        if (! $this->params->get('day')) {
            $tabs->add('heatmap', [
                'label' => $this->translate('Heatmap'),
                'url' => 'vspheredb/vmotions/heatmap'
            ]);
        }

        $tabs->activate($this->getRequest()->getActionName());
    }
}
