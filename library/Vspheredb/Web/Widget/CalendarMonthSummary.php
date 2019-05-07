<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use ipl\Html\HtmlElement;
use gipfl\IcingaWeb2\Link;
use ipl\Html\Table;
use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Url;
use gipfl\Calendar\Calendar;

class CalendarMonthSummary extends Table
{
    use TranslationHelper;

    protected $defaultAttributes = [
        'data-base-target' => '_next',
        'class'            => 'calendar',
    ];

    protected $today;

    protected $year;

    protected $month;

    protected $strMonth;

    protected $strToday;

    protected $days = [];

    protected $calendar;

    protected $showWeekNumbers = true;

    protected $showOtherMonth = false;

    protected $showGrayFuture = true;

    protected $title;

    protected $color = '255, 128, 0';

    protected $forcedMax;

    public function __construct($year, $month)
    {
        $this->calendar = new Calendar();
        $this->year = $year;
        $this->month = $month;
        $this->strMonth = sprintf('%d-%02d', $year, $month);
        $this->strToday = date('Y-m-d');
    }

    public function setRgb($red, $green, $blue)
    {
        $this->color = sprintf('%d, %d, %d', $red, $green, $blue);

        return $this;
    }

    public function addEvents($events, Url $baseUrl)
    {
        if (empty($events)) {
            return $this;
        }

        if ($this->forcedMax === null) {
            $max = max($events);
        } else {
            $max = $this->forcedMax;
        }

        foreach ($events as $day => $count) {
            if (! $this->hasDay($day)) {
                continue;
            }
            $text = (int) substr($day, -2);

            $link = Link::create($text, $baseUrl->with('day', $day));
            $alpha = $count / $max;

            if ($alpha > 0.4) {
                $link->addAttributes(['style' => 'color: white;']);
            }
            $link->addAttributes([
                'title' => sprintf('%d events', $count),
                'style' => sprintf(
                    'background-color: rgba(%s, %.2F);',
                    $this->color,
                    $alpha
                )
            ]);

            $this->getDay($day)->setContent($link);
        }

        return $this;
    }

    public function markNow($now = null)
    {
        if ($now === null) {
            $now = time();
        }
        $this->today = date('Y-m-d', $now);

        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    protected function getTitle()
    {
        if ($this->title === null) {
            $this->title = $this->getMonthName() . ' ' . $this->year;
        }

        return $this->title;
    }

    public function forceMax($max)
    {
        $this->forcedMax = $max;

        return $this;
    }

    protected function getMonthAsTimestamp()
    {
        return strtotime($this->strMonth . '-01');
    }

    protected function assemble()
    {
        $this->setCaption($this->getTitle());
        $this->header()->add($this->createWeekdayHeader());
        $calendar = new Calendar();
        foreach ($calendar->getWeeksForMonth($this->getMonthAsTimestamp()) as $cw => $week) {
            $weekRow = $this->weekRow($cw);
            foreach ($week as $day) {
                $weekRow->add($this->createDay($day));
            }
            $this->body()->add($weekRow);
        }
    }

    /**
     * @param $day
     * @return HtmlElement
     */
    protected function getDay($day)
    {
        $this->ensureAssembled();
        return $this->days[$day];
    }

    protected function hasDay($day)
    {
        $this->ensureAssembled();

        return isset($this->days[$day]);
    }

    protected function createDay($day)
    {
        $otherMonth = substr($day, 0, 7) !== $this->strMonth;
        $title = (int) substr($day, -2);
        if ($otherMonth && ! $this->showOtherMonth) {
            $title = '';
        }
        $td = Table::td($title);
        $this->days[$day] = $td;

        if ($otherMonth) {
            $td->addAttributes(['class' => 'other-month']);
        } elseif ($this->showGrayFuture && $day > $this->strToday) {
            $td->addAttributes(['class' => 'future-day']);
        }

        // TODO: today VS strToday?!
        if ($day === $this->today) {
            $td->addAttributes(['class' => 'today']);
        }

        return $td;
    }


    protected function weekRow($cw)
    {
        $row = Table::tr();

        if ($this->showWeekNumbers) {
            $row->add(Table::th(sprintf('%02d', $cw), [
                'title' => sprintf($this->translate('Calendar Week %d'), $cw)
            ]));
        }

        return $row;
    }

    protected function getMonthName()
    {
        return strftime('%B', $this->getMonthAsTimestamp());
    }

    protected function createWeekdayHeader()
    {
        $cols = $this->calendar->listShortWeekDayNames();
        if ($this->showWeekNumbers) {
            array_unshift($cols, '');
        }

        return Table::row($cols, null, 'th');
    }
}
