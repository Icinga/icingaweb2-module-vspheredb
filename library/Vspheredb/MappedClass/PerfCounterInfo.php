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

    /**
     * Minimum level at which metrics of this type will be collected by
     * VirtualCenter Server. The value for this property for any performance
     * counter is a number from 1 to 4. The higher the setting, the more data
     * is collected by VirtualCenter Server.
     *
     * The default setting for VirtualCenter Server is 1, which collects the
     * minimal amount of performance data that is typically useful to
     * administrators and developers alike. The specific level of each counter
     * is documented in the respective counter-documentation pages, by group.
     *
     * See PerformanceManager for links to the counter group pages.
     *
     * @var int
     */
    public $level;

    /** @var ElementDescription */
    public $nameInfo;

    /**
     * Minimum level at which the per device metrics of this type will be
     * collected by vCenter Server. The value for this property for any
     * performance counter is a number from 1 to 4. By default all per device
     * metrics are calculated at level 3 or more.
     *
     * If a certain per device counter is collected at a certain level, the
     * aggregate metric is also calculated at that level, i.e., perDeviceLevel
     * is greater than or equal to level.
     *
     * @var int
     */
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
