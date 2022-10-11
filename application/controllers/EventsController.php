<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Web\Widget\Hint;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Form\FilterHostParentForm;
use Icinga\Module\Vspheredb\Web\Table\EventHistoryTable;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\CalendarForEvents;
use Icinga\Module\Vspheredb\Web\Widget\VMotionHeatmap;
use Ramsey\Uuid\Uuid;

class EventsController extends Controller
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
            'vspheredb/events/heatmap',
            $this->url()->getParams()->toArray(false),
            [
                'class' => 'icon-calendar',
                'data-base-target' => '_main',
            ]
        ));

        $form = $this->addFilterForm();
        $day = $this->params->get('day');

        $table = new EventHistoryTable($this->db());
        $table
            ->filterEventType($form->getElement('type')->getValue())
            ->filterParent($form->getElement('parent')->getValue());
        if ($day) {
            $dayStamp = strtotime($day);
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
            $this->content()->add(Hint::warning($this->translate('No events found')));
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
        $heatMap = new VMotionHeatmap($this->db());
        $heatMap->filterEventType($form->getElement('type')->getValue());
        if ($parent = $form->getElement('parent')->getValue()) {
            $heatMap->filterParent(Uuid::fromString($parent)->getBytes());
        }
        $baseUrl = Url::fromPath('vspheredb/events')->setParams(
            $this->url()->getParams()
        );
        $this->content()->add(new CalendarForEvents($heatMap, $baseUrl, $form->getColors()));
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
