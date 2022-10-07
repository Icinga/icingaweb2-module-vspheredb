<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * This option specifies a time range used to filter event history
 *
 * #[AllowDynamicProperties]
 */
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
     * @return static
     */
    public static function create($beginTime = null, $endTime = null)
    {
        $self = new static();
        if ($beginTime) {
            if (is_int($beginTime)) {
                $self->beginTime = self::makeDateTime($beginTime);
            } else {
                $self->beginTime = $beginTime;
            }
        }
        if ($endTime) {
            if (is_int($endTime)) {
                $self->endTime = self::makeDateTime($endTime);
            } else {
                $self->endTime = $endTime;
            }
        }

        return $self;
    }

    /**
     * DateTime for SOAP call
     * @param $timestamp
     * @return string
     */
    protected static function makeDateTime($timestamp)
    {
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }
}
