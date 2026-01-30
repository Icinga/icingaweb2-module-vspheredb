<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Widget\Hint;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class CalendarForEvents extends HtmlDocument
{
    use TranslationHelper;

    /** @var VMotionHeatmap|AlarmHeatmap */
    protected VMotionHeatmap|AlarmHeatmap $calendars;

    /** @var Url */
    protected Url $baseUrl;

    /** @var int[] [r, g, b] */
    protected array $colors;

    public function __construct(VMotionHeatmap|AlarmHeatmap $calendars, Url $baseUrl, array $colors)
    {
        $this->calendars = $calendars;
        $this->baseUrl = $baseUrl;
        $this->colors = $colors;
    }

    protected function assemble(): void
    {
        $events = $this->calendars->getEvents();
        if (empty($events)) {
            $this->add(Hint::warning($this->translate('No events found')));
            $maxPerDay = $total = 0;
        } else {
            $maxPerDay = max($events);
            $total = array_sum($events);
            $this->add(Hint::ok(
                $this->translate('%s events, max %s per day'),
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

        $months = $this->prepareMonthList();
        $colors = $this->colors;
        /** @var string $yearMonth */
        foreach (array_reverse($months) as $yearMonth) {
            $year = (int) substr($yearMonth, 0, 4);
            $month = (int) substr($yearMonth, -2);
            $cal = new CalendarMonthSummary($year, $month);
            $cal->setRgb($colors[0], $colors[1], $colors[2])
                ->markNow()
                ->forceMax($maxPerDay);
            if (isset($eventsPerMonth[$yearMonth])) {
                $cal->addEvents($eventsPerMonth[$yearMonth], $this->baseUrl);
            }

            $div->add($cal);
        }
        $this->add($div);
    }

    /**
     * @return string[]
     */
    protected function prepareMonthList(): array
    {
        $today = date('Y-m-15');
        $months = [substr($today, 0, 7)];
        for ($i = 1; $i < 12; $i++) {
            $today = date('Y-m-d', strtotime("$today -1 month"));
            $months[] = substr($today, 0, 7);
        }

        return array_reverse($months);
    }
}
