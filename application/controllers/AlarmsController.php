<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Table\AlarmHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\AlarmHeatmap;

class AlarmsController extends Controller
{
    /**
     * @throws \Icinga\Exception\Http\HttpNotFoundException
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function init()
    {
        $this->handleTabs();
    }

    /**
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function indexAction()
    {
        $day = $this->params->shift('day');

        $table = new AlarmHistoryTable($this->db());
        $dayStamp = strtotime($day);
        if ($day) {
            $this->addTitle('Alarm History on %s', DateFormatter::formatDate($dayStamp));
            $table->getQuery()->where(
                'ts_event_ms >= ?',
                strtotime("$day 00:00:00") * 1000
            )->where(
                'ts_event_ms <= ?',
                (strtotime("$day 23:59:59") + 1) * 1000
            );
        } else {
            $this->addTitle('Alarm History');
        }
        $table->renderTo($this);
    }

    public function heatmapAction()
    {
        $this->addTitle('Alarm Heatmap');
        $this->content()->add(AlarmHeatmap::create($this->vCenter(), 'vspheredb/alarms'));
    }

    /**
     * @throws \Icinga\Exception\Http\HttpNotFoundException
     * @throws \Icinga\Exception\ProgrammingError
     */
    protected function handleTabs()
    {
        $tabs = $this->tabs()->add('index', [
            'label' => $this->translate('Alarm History'),
            'url' => 'vspheredb/alarms'
        ]);

        if (! $this->params->get('day')) {
            $tabs->add('heatmap', [
                'label' => $this->translate('Heatmap'),
                'url' => 'vspheredb/alarms/heatmap'
            ]);
        }

        $tabs->activate($this->getRequest()->getActionName());
    }
}
