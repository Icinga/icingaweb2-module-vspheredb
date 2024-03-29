<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * Tolerance and frequency limits of an alarm.
 */
class AlarmSetting extends DynamicData
{
    /**
     * How often the alarm is triggered, measured in seconds.
     *
     * A zero value means that the alarm is allowed to trigger as often as
     * possible. A nonzero value means that any subsequent triggers are
     * suppressed for a period of seconds following a reported trigger.
     *
     * @var int
     */
    public $reportingFrequency;

    /**
     * Tolerance range for the metric triggers, measured in one hundredth
     * percentage.
     *
     * A zero value means that the alarm triggers whenever the metric value is
     * above or below the specified value. A nonzero value means that the alarm
     * triggers only after reaching a certain percentage above or below the
     * nominal trigger value.
     *
     * @var int
     */
    public $toleranceRange;
}
