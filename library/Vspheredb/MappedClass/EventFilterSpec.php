<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

/**
 * Event filter used to query events in the history collector database. The
 * client creates an event history collector with a filter specification, the
 * retrieves the events from the event history collector.
 *
 * #[AllowDynamicProperties]
 */
class EventFilterSpec
{
    /**
     * This property, if set, limits the set of collected events to those associated
     * with the specified alarm. If the property is not set, events are collected
     * regardless of their association with alarms.
     *
     * @var ?ManagedObjectReference to an Alarm
     */
    public $alarm;

    /**
     * This property, if set, limits the set of collected events to those associated
     * with the specified category. If the property is not set, events are collected
     * regardless of their association with any category. "category" here is the same
     * as Event.severity.
     *
     * @var ?array
     */
    public $category;

    /**
     * Flag to specify whether to prepare the full formatted message for each event.
     * If the property is not set, the collected events do not include the full
     * formatted message.
     *
     * @var ?boolean
     */
    public $disableFullMessage;

    /**
     * The filter specification for retrieving events by managed entity. If the
     * property is not set, then events attached to all managed entities are collected.
     *
     * @var ?EventFilterSpecByEntity
     */
    public $entity;


    /**
     * The filter specification for retrieving events by chain ID. If the
     * property is not set, events with any chain ID are collected.
     *
     * @var ?int
     */
    public $eventChainId;

    /**
     * This property, if set, limits the set of collected events to those
     * specified types
     *
     * Note: if both eventTypeId and type are specified, an exception may be
     * thrown by CreateCollectorForEvents.
     *
     * The semantics of how eventTypeId matching is done is as follows:
     *
     * If the event being collected is of type EventEx or ExtendedEvent, then
     * we match against the eventTypeId (for EventEx) or eventId (for
     * ExtendedEvent) member of the Event.
     *
     * Otherwise, we match against the type of the Event itself.
     *
     * If neither this property, nor type, is set, events are collected
     * regardless of their types.
     *
     * Since vSphere API 4.0
     *
     * @var ?string[]
     */
    public $eventTypeId;

    /**
     * This property, if set, limits the set of collected events to those
     * associated with the specified scheduled task. If the property is not set,
     * events are collected regardless of their association with any scheduled
     * task.
     *
     * @var ManagedObjectReference to a ScheduledTask
     */
    public $scheduledTask;

    /**
     * This property, if set, limits the set of filtered events to those that
     * have it. If not set, or the size of it 0, the tag of an event is
     * disregarded. A blank string indicates events without tags.
     *
     * Since vSphere API 4.0
     *
     * @var ?string[]
     */
    public $tag;

    /**
     * The filter specification for retrieving tasks by time. If the property
     * is not set, then events with any time stamp are collected.
     *
     * @var ?EventFilterSpecByTime
     */
    public $time;

    /**
     * Deprecated. As of vSphere API 4.0, use eventTypeId instead.
     *
     * This property, if set, limits the set of collected events to those
     * specified types. If the property is not set, events are collected
     * regardless of their types.
     *
     * @var ?string[]
     */
    public $type;

    /**
     * The filter specification for retrieving events by username. If the
     * property is not set, then events belonging to any user are collected
     *
     * @var ?EventFilterSpecByUsername
     */
    public $userName;
}
