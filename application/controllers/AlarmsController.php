<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Table\AlarmHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\AlarmHeatmap;
use Icinga\Module\Vspheredb\Web\Widget\CalendarForEvents;

class AlarmsController extends Controller
{
    public function init()
    {
        $this->assertPermission('vspheredb/admin');
        parent::init();
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
        if ($day) {
            $dayStamp = strtotime($day);
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
        $this->addTitle($this->translate('Alarm Heatmap'));
        $heatMap = new AlarmHeatmap($this->db());
        $baseUrl = Url::fromPath('vspheredb/alarms')->setParams(
            $this->url()->getParams()
        );
        $this->content()->add(new CalendarForEvents($heatMap, $baseUrl, [255, 0, 0]));
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
