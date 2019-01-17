<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class PerfCounterInfo
{
    /** @var int[] */
    public $associatedCounterId;

    /** @var ElementDescription */
    public $groupInfo;

    /** @var int */
    public $key;

    /** @var int */
    public $level;

    /** @var ElementDescription */
    public $nameInfo;

    /** @var int */
    public $perDeviceLevel;

    /**
     * Enum - PerfSummaryType:
     *
     * - average   : The actual value collected or the average of all values collected during the summary period
     * - latest    : The most recent value of the performance counter over the summarization period
     * - maximum   : The maximum value of the performance counter value over the summarization period
     * - minimum   : The minimum value of the performance counter value over the summarization period
     * - none      : The counter is never rolled up
     * - summation : The sum of all the values of the performance counter over the summarization period
     *
     * @var string
     */
    public $rollupType;

    /**
     * Enum - PerfStatsType
     *
     * - absolute : Represents an actual value, level, or state of the counter. For example, the “uptime” counter
     *              (system group) represents the actual number of seconds since startup. The “capacity” counter
     *              represents the actual configured size of the specified datastore. In other words, number of
     *              samples, samplingPeriod, and intervals have no bearing on an “absolute” counter“s value.
     * - delta    : Represents an amount of change for the counter during the samplingPeriod as compared to the
     *              previous interval. The first sampling interval
     * - rate     : Represents a value that has been normalized over the samplingPeriod, enabling values for the
     *              same counter type to be compared, regardless of interval. For example, the number of reads
     *              per second.
     *
     * @var string
     */
    public $statsType;

    /** @var ElementDescription */
    public $unitInfo;
}
