<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\Calendar\Calendar;
use gipfl\Format\LocalTimeFormat;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Web\Compat\StyleWithNonce;

class CalendarMonthSummary extends Table
{
    use TranslationHelper;

    protected $defaultAttributes = [
        'data-base-target' => '_next',
        'class'            => 'calendar',
    ];

    protected ?string $today = null;

    protected int $year;

    protected int $month;

    protected string $strMonth;

    protected string $strToday;

    protected array $days = [];

    protected Calendar $calendar;

    protected bool $showWeekNumbers = true;

    protected bool $showOtherMonth = false;

    protected bool $showGrayFuture = true;

    protected ?string $title = null;

    protected string $color = '255, 128, 0';

    protected ?int $forcedMax = null;

    protected LocalTimeFormat $timeFormat;

    public function __construct(int $year, int $month)
    {
        $this->calendar = new Calendar();
        $this->year = $year;
        $this->month = $month;
        $this->strMonth = sprintf('%d-%02d', $year, $month);
        $this->strToday = date('Y-m-d');
        $this->timeFormat = new LocalTimeFormat();
    }

    public function setRgb(int $red, int $green, int $blue): static
    {
        $this->color = sprintf('%d, %d, %d', $red, $green, $blue);

        return $this;
    }

    public function addEvents(array $events, Url $baseUrl): static
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
                $link->addAttributes(Attributes::create(['class' => 'color-white']));
            }

            $style = (new StyleWithNonce())
                ->setModule('vspheredb')
                ->addFor($link, ['background-color' => sprintf('rgba(%s, %.2F)', $this->color, $alpha)]);

            $link->addAttributes(Attributes::create(['title' => sprintf('%d events', $count)]));

            $this->getDay($day)->setContent([$link, $style]);
        }

        return $this;
    }

    public function markNow(?int $now = null): static
    {
        if ($now === null) {
            $now = time();
        }
        $this->today = date('Y-m-d', $now);

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    protected function getTitle(): string
    {
        if ($this->title === null) {
            $this->title = $this->getMonthName() . ' ' . $this->year;
        }

        return $this->title;
    }

    public function forceMax(int $max): static
    {
        $this->forcedMax = $max;

        return $this;
    }

    protected function getMonthAsTimestamp(): int
    {
        return strtotime($this->strMonth . '-01');
    }

    protected function assemble(): void
    {
        $this->setCaption($this->getTitle());
        $this->getHeader()->add($this->createWeekdayHeader());
        $calendar = new Calendar();
        foreach ($calendar->getWeeksForMonth($this->getMonthAsTimestamp()) as $cw => $week) {
            $weekRow = $this->weekRow($cw);
            foreach ($week as $day) {
                $weekRow->add($this->createDay($day));
            }
            $this->getBody()->add($weekRow);
        }
    }

    /**
     * @param string $day
     *
     * @return HtmlElement
     */
    protected function getDay(string $day): HtmlElement
    {
        $this->ensureAssembled();

        return $this->days[$day];
    }

    /**
     * @param string $day
     *
     * @return bool
     */
    protected function hasDay(string $day): bool
    {
        $this->ensureAssembled();

        return isset($this->days[$day]);
    }

    /**
     * @param string $day
     *
     * @return HtmlElement
     */
    protected function createDay(string $day): HtmlElement
    {
        $otherMonth = substr($day, 0, 7) !== $this->strMonth;
        $title = (int) substr($day, -2);
        if ($otherMonth && ! $this->showOtherMonth) {
            $title = '';
        }
        $td = Table::td($title);
        $this->days[$day] = $td;

        if ($otherMonth) {
            $td->addAttributes(Attributes::create(['class' => 'other-month']));
        } elseif ($this->showGrayFuture && $day > $this->strToday) {
            $td->addAttributes(Attributes::create(['class' => 'future-day']));
        }

        // TODO: today VS strToday?!
        if ($day === $this->today) {
            $td->addAttributes(Attributes::create(['class' => 'today']));
        }

        return $td;
    }


    protected function weekRow($cw): HtmlElement
    {
        $row = Table::tr();

        if ($this->showWeekNumbers) {
            $row->add(Table::th(sprintf('%02d', $cw), [
                'title' => sprintf($this->translate('Calendar Week %d'), $cw)
            ]));
        }

        return $row;
    }

    protected function getMonthName(): string
    {
        return $this->timeFormat->getMonthName($this->getMonthAsTimestamp());
        return date('F', $this->getMonthAsTimestamp());
    }

    protected function createWeekdayHeader(): HtmlElement
    {
        $cols = $this->calendar->listShortWeekDayNames();
        if ($this->showWeekNumbers) {
            array_unshift($cols, '');
        }

        return Table::row($cols, null, 'th');
    }
}
