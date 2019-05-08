<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Form\FilterHostParentForm;
use Icinga\Module\Vspheredb\Web\Table\EventHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\CalendarMonthSummary;
use Icinga\Module\Vspheredb\Web\Widget\VMotionHeatmap;
use ipl\Html\Html;

class EventsController extends Controller
{
    public function init()
    {
        $this->handleTabs();
    }

    public function indexAction()
    {
        $this->actions()->add(Link::create(
            $this->translate('Calendar'),
            'vspheredb/events/heatmap',
            $this->url()->getParams()->toArray(false),
            [
                'class' => 'icon-calendar',
                'data-base-target' => '_main',
            ]
        ));

        $form = $this->addFilterForm();
        $day = $this->params->shift('day');

        $table = new EventHistoryTable($this->db());
        $table
            ->filterEventType($form->getElement('type')->getValue())
            ->filterParent($form->getElement('parent')->getValue());
        $dayStamp = strtotime($day);
        if ($day) {
            $this->addTitle('Event History on %s', DateFormatter::formatDate($dayStamp));
            $table->getQuery()->where(
                'ts_event_ms >= ?',
                strtotime("$day 00:00:00") * 1000
            )->where(
                'ts_event_ms <= ?',
                (strtotime("$day 23:59:59") + 1) * 1000
            );
        } else {
            $this->addTitle($this->translate('Event History'));
        }
        $count = count($table);
        if ($count === 0) {
            $this->addHint($this->translate('No events found'), 'warning');
        }

        $table->renderTo($this);
    }

    protected function addFilterForm()
    {
        $form = new FilterHostParentForm($this->db());
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);

        return $form;
    }

    protected function addHint($message, $class = 'information')
    {
        $this->content()->add(Html::tag('p', [
            'class' => $class
        ], $message));

        return $this;
    }

    public function heatmapAction()
    {
        $this->actions()->add(Link::create(
            $this->translate('Table'),
            'vspheredb/events',
            $this->url()->getParams()->toArray(false),
            [
                'class' => 'icon-th-list',
                'data-base-target' => '_main',
            ]
        ));

        $this->addTitle($this->translate('Event HeatMap'));

        $form = $this->addFilterForm();
        $heatMap = new VMotionHeatmap($this->db(), 'vspheredb/events');
        $heatMap->filterEventType($form->getElement('type')->getValue());
        if ($parent = $form->getElement('parent')->getValue()) {
            $heatMap->filterParent(hex2bin($parent));
        }
        $events = $heatMap->getEvents();
        if (empty($events)) {
            $this->addHint('No events found', 'warning');
            $maxPerDay = $total = 0;
        } else {
            $maxPerDay = max($events);
            $total = array_sum($events);
            $this->addHint(sprintf(
                '%d events, max %d per day',
                $total,
                $maxPerDay
            ));
        }

        $eventsPerMonth = [];
        foreach ($events as $day => $count) {
            $month = substr($day, 0, 7);
            $eventsPerMonth[$month][$day] = $count;
        }
        $div = Html::tag('div', [
            'class' => 'event-heatmap-calendars',
        ]);
        $baseUrl = Url::fromPath('vspheredb/events')->setParams(
            $this->url()->getParams()
        );

        $months = $this->prepareMonthList();
        $colors = $form->getColors();
        foreach (array_reverse($months) as $yearMonth) {
            $year = (int) substr($yearMonth, 0, 4);
            $month = (int) substr($yearMonth, -2);
            $cal = new CalendarMonthSummary($year, $month);
            $cal->setRgb($colors[0], $colors[1], $colors[2])
                ->markNow()
                ->forceMax($maxPerDay);
            if (isset($eventsPerMonth[$yearMonth])) {
                $cal->addEvents($eventsPerMonth[$yearMonth], $baseUrl);
            }

            $div->add($cal);
        }

        $this->content()->add($div);
    }

    protected function prepareMonthList()
    {
        $today = date('Y-m-15');
        $months = [substr($today, 0, 7)];
        for ($i = 1; $i < 12; $i++) {
            $today = date('Y-m-d', strtotime("$today -1 month"));
            $months[] = substr($today, 0, 7);
        }

        return array_reverse($months);
    }

    protected function handleTabs()
    {
        $params = [];
        if ($day = $this->params->get('day')) {
            $params['day'] = $day;
            $alarmsUrl = 'vspheredb/alarms';
        } else {
            $alarmsUrl = 'vspheredb/alarms/heatmap';
        }
        $tabs = $this->tabs()->add('events', [
            'label' => $this->translate('Events'),
            'url'   => $this->url(),
        ])->add('alarms', [
            'label' => $this->translate('Alarms'),
            'url'   => $alarmsUrl,
            'urlParams' => $params
        ]);

        $tabs->activate($this->getRequest()->getControllerName());
    }
}
