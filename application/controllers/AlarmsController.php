<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Table\AlarmHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\AlarmHeatmap;

class AlarmsController extends Controller
{
    public function init()
    {
        $this->handleTabs();
    }

    public function indexAction()
    {
        $this->actions()->add(Link::create(
            $this->translate('Calendar'),
            'vspheredb/alarms/heatmap',
            $this->url()->getParams()->toArray(false),
            [
                'class' => 'icon-calendar',
                'data-base-target' => '_main',
            ]
        ));
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
        $this->actions()->add(Link::create(
            $this->translate('Table'),
            'vspheredb/alarms',
            $this->url()->getParams()->toArray(false),
            [
                'class' => 'icon-th-list',
                'data-base-target' => '_main',
            ]
        ));
        $this->addTitle('Alarm Heatmap');
        $this->content()->add(new AlarmHeatmap($this->db(), 'vspheredb/alarms'));
    }

    protected function handleTabs()
    {
        $params = [];
        if ($day = $this->params->get('day')) {
            $params['day'] = $day;
            $eventsUrl = 'vspheredb/events';
        } else {
            $eventsUrl = 'vspheredb/events/heatmap';
        }
        $tabs = $this->tabs()->add('events', [
            'label' => $this->translate('Events'),
            'url'   => $eventsUrl,
            'urlParams' => $params
        ])->add('alarms', [
            'label' => $this->translate('Alarms'),
            'url'   => $this->url()
        ]);

        $tabs->activate($this->getRequest()->getControllerName());
    }
}
