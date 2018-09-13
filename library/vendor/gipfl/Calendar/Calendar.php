<?php

namespace gipfl\Calendar;

use InvalidArgumentException;

class Calendar
{
    const FIRST_IS_MONDAY = 1;
    const FIRST_IS_SUNDAY = 0;

    protected $firstOfWeek;

    public function __construct($firstOfWeek = self::FIRST_IS_MONDAY)
    {
        if ($firstOfWeek === self::FIRST_IS_SUNDAY || $firstOfWeek === self::FIRST_IS_MONDAY) {
            $this->firstOfWeek = $firstOfWeek;
        } else {
            throw new InvalidArgumentException(
                "First day of week has to be either 0 or 1, got '$firstOfWeek'"
            );
        }
    }

    public function listWeekDayNames()
    {
        if ($this->firstOfWeek === 0) {
            return [
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
            ];
        } else {
            return [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ];
        }
    }

    public function listShortWeekDayNames()
    {
        if ($this->firstOfWeek === 0) {
            return [
                'Su',
                'Mo',
                'Tu',
                'We',
                'Th',
                'Fr',
                'Sa',
            ];
        } else {
            return [
                'Mo',
                'Tu',
                'We',
                'Th',
                'Fr',
                'Sa',
                'Su',
            ];
        }
    }

    /**
     * Either 'N' or 'w', depending on the first day of week
     *
     * @return string
     */
    protected function getDowFormat()
    {
        if ($this->firstOfWeek === self::FIRST_IS_MONDAY) {
            // N -> 1-7 (Mo-Su)
            return 'N';
        } else {
            // w -> 0-6 (Su-Sa)
            return 'w';
        }
    }

    /**
     * @param $time
     * @return int
     */
    protected function getWeekDay($time)
    {
        return (int) date($this->getDowFormat(), $time);
    }

    /**
     * @param int $now
     * @return array
     */
    public function getDaysForWeek($now)
    {
        $formatDow = $this->getDowFormat();
        $today = date('Y-m-d', $now);
        $day = $this->getFirstDayOfWeek($today);
        $weekday = (int) date($formatDow, strtotime($day));
        $week = [$weekday => $day];
        for ($i = 1; $i < 7; $i++) {
            $day = date('Y-m-d', strtotime("$day +1day"));
            $weekday = (int) date($formatDow, strtotime($day));
            $week[$weekday] = $day;
        }

        return $week;
    }

    /**
     * @param int $now
     * @return array
     */
    public function getWorkingDaysForWeek($now)
    {
        $formatDow = $this->getDowFormat();
        $today = date('Y-m-d', $now);
        $day = $this->getFirstDayOfWeek($today, self::FIRST_IS_MONDAY);
        $weekday = (int) date($formatDow, strtotime($day));
        $week = [$weekday => $day];
        for ($i = 1; $i < 5; $i++) {
            $day = date('Y-m-d', strtotime("$day +1day"));
            $weekday = (int) date($formatDow, strtotime($day));
            $week[$weekday] = $day;
        }

        return $week;
    }

    /**
     * @param string $day
     * @param int $firstOfWeek
     * @return string
     */
    protected function getFirstDayOfWeek($day, $firstOfWeek = null)
    {
        if ($firstOfWeek === null) {
            $firstOfWeek = $this->firstOfWeek;
        }
        $dow = $this->getWeekDay(strtotime($day));
        if ($dow > $firstOfWeek) {
            $sub = $dow - 1;
            return date('Y-m-d', strtotime("$day -${sub}day"));
        } else {
            return $day;
        }
    }

    /**
     * @param string $day
     * @return string
     */
    protected function getLastDayOfWeek($day)
    {
        $dow = $this->getWeekDay(strtotime($day));
        $lastOfWeek = $this->firstOfWeek + 6;
        if ($dow < $lastOfWeek) {
            $add = $lastOfWeek - $dow;
            return date('Y-m-d', strtotime("$day +${add}day"));
        } else {
            return $day;
        }
    }

    /**
     * @param int $now
     * @return array
     */
    public function getWeeksForMonth($now)
    {
        $first = date('Y-m-01', $now);
        $last = date('Y-m-d', strtotime("$first +1month -1day"));

        $formatDow = $this->getDowFormat();
        $end = $this->getLastDayOfWeek($last);
        $day = $this->getFirstDayOfWeek($first);
        $formerWeekOfTheYear = 0;
        $weeks = [];
        while ($day <= $end) {
            $weekOfTheYear = (int) date('W', strtotime($day));
            if ($weekOfTheYear !== $formerWeekOfTheYear) {
                $weeks[$weekOfTheYear] = [];
                $week = & $weeks[$weekOfTheYear];
            }

            $weekday = (int) date($formatDow, strtotime($day));
            $week[$weekday] = $day;
            $day = date('Y-m-d', strtotime("$day +1day"));
            $formerWeekOfTheYear = $weekOfTheYear;
        }

        return $weeks;
    }

    protected function unusedOnlyForTranslation()
    {
        // TODO: move elsewhere
        return [
            $this->translate('Monday'),
            $this->translate('Tuesday'),
            $this->translate('Wednesday'),
            $this->translate('Thursday'),
            $this->translate('Friday'),
            $this->translate('Saturday'),
            $this->translate('Sunday'),
        ];
    }
}
