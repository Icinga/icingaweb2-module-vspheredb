<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use dipl\Html\BaseHtmlElement;
use dipl\Html\Html;
use dipl\Html\Link;
use dipl\Html\Table;
use dipl\Web\Url;

class EventHeatmapCalendars extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => 'event-heatmap-calendars'
    ];

    /** @var Url */
    protected $baseUrl;

    protected function newTable($month)
    {
        $table = new Table();
        $table->addAttributes(['data-base-target' => '_next', 'class' => 'calendar']);

        $body = $table->body();
        $body->add($table::tr($table::th(
            $month,
            [
                'colspan' => 7,
                'style' => 'text-align: center'
            ])
        ));
        $body->add($this->createWeekdayHeader());

        $this->add($table);

        return $table;
    }

    protected function createWeekdayHeader()
    {
        return Table::row([
            'Mo',
            'Tu',
            'We',
            'Th',
            'Fr',
            'Sa',
            'So',
        ], null, 'th');
    }

    protected function setBaseUrl($baseUrl)
    {
        if (is_string($baseUrl)) {
            $this->baseUrl = Url::fromPath($baseUrl);
        } else {
            $this->baseUrl = $baseUrl;
        }
    }

    public function __construct($events, $baseUrl)
    {
        $this->setBaseUrl($baseUrl);
        $max = max($events);

        $firstRow = true;
        $table = $body = $row = null;

        $weekday = '7';

        $lastMonth = null;

        foreach ($events as $day => $count) {
            $time = strtotime($day);
            $month = strftime('%B', $time);
            $dayOfMonth = strftime('%e', $time);
            $weekday = strftime('%u', $time);

            if ($month !== $lastMonth) {
                $table = $this->newTable($month);
                $body = $table->body();

                if ($row !== null) {
                    $this->closeWeek($row, $weekday);
                }

                $row = $table::tr();
                $body->add($row);
                $this->prefillWeek($row, $weekday);

                $lastMonth = $month;
            }

            if ($weekday === '1') {
                $row = $table::tr();
                $body->add($row);
            }
            if ($firstRow) {
                $firstRow = false;
            }

            $link = Link::create($dayOfMonth, $this->baseUrl->with('day', $day));
            $alpha = $count / $max;

            if ($alpha > 0.4) {
                $link->addAttributes(['style' => 'color: white;']);
            }
            $link->addAttributes([
                'title' => sprintf('%d events', $count),
                'style' => sprintf(
                    'background-color: rgba(255, 0, 0, %.2F);',
                    $alpha
                )
            ]);
            $row->add(Table::td($link));
        }

        $this->closeWeek($row, $weekday);
    }

    protected function prefillWeek(BaseHtmlElement $row, $weekday)
    {
        for ($i = 1; $i < $weekday; $i++) {
            $row->add(Html::tag('td', null, ''));
        }
    }

    protected function closeWeek(BaseHtmlElement $row, $weekday)
    {
        for ($i = $weekday + 1; $i <= 7; $i++) {
            $row->add(Html::tag('td', null, ''));
        }
    }
}
