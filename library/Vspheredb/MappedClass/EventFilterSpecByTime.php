<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

/**
 * This option specifies a time range used to filter event history
 */
#[AllowDynamicProperties]
class EventFilterSpecByTime
{
    /**
     * The beginning of the time range. If this property is not set, then events
     * are collected from the earliest time in the database.
     *
     * @var ?string dateTime
     */
    public $beginTime;

    /**
     * The end of the time range. If this property is not specified, then events
     * are collected up to the latest time in the database.
     *
     * @var ?string dateTime
     */
    public $endTime;

    /**
     * @param ?int|string $beginTime
     * @param ?int|string $endTime
     *
     * @return static
     */
    public static function create($beginTime = null, $endTime = null)
    {
        $self = new static();
        if ($beginTime) {
            $self->beginTime = is_int($beginTime) ? self::makeDateTime($beginTime) : $beginTime;
        }
        if ($endTime) {
            $self->endTime = is_int($endTime) ? self::makeDateTime($endTime) : $endTime;
        }

        return $self;
    }

    /**
     * DateTime for SOAP call
     *
     * @param $timestamp
     *
     * @return string
     */
    protected static function makeDateTime($timestamp)
    {
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }
}
