<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\DbObject\ManagedObject;

class PerfQuerySpec
{
    /** @var ManagedObject */
    public $entity;

    /**
     * The server time from which to obtain counters. If not specified, defaults
     * to the first available counter. When a startTime is specified, the returned
     * samples do not include the sample at startTime.
     *
     * @var string|null xsd:dateTime
     */
    public $startTime;

    /**
     * The time up to which statistics are retrieved. Corresponds to server time.
     * When endTime is omitted, the returned result includes up to the most recent
     * metric value. When an endTime is specified, the returned samples include
     * the sample at endTime.
     *
     * @var string|null xsd:dateTime
     */
    public $endTime;

    /**
     * The interval (samplingPeriod), in seconds, for the performance statistics.
     * For aggregated information, use one of the historical intervals for this
     * property. See PerfInterval for more information.
     *
     * To obtain the greatest detail, use the provider’s refreshRate for this
     * property.
     *
     * @var int|null
     */
    public $intervalId;

    /**
     * Limits the number of samples returned. Defaults to the most recent sample
     * (or samples), unless a time range is specified. Use this property only in
     * conjunction with the intervalId to obtain real-time statistics (set the
     * intervalId to the refreshRate.
     *
     * This property is ignored for historical statistics, and is not valid for
     * the QueryPerfComposite operation.
     *
     * @var int|null
     */
    public $maxSample;

    /** @var string|null enum PerfFormat, 'normal' or 'csv' */
    public $format;

    /** @var PerfMetricId[]|null */
    public $metricId;
}
