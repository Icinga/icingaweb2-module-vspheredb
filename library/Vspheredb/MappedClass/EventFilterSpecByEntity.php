<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

/**
 * This option specifies a managed entity used to filter event history. If the
 * specified managed entity is a Folder or a ResourcePool, the query will
 * actually be performed on the entities contained within that Folder or
 * ResourcePool, so you cannot query for events on Folders and ResourcePools
 * themselves this way.
 */
class EventFilterSpecByEntity
{
    /**
     * The managed entity to which the event pertains
     *
     * @var ManagedObjectReference to a ManagedEntity
     */
    public $entity;

    /**
     * Specification of related managed entities in the inventory hierarchy:
     *
     *  - all:      Returns events pertaining either to the specified managed
     *              entity or to its child entities
     *  - children: Returns events pertaining to child entities only. Excludes
     *              events pertaining to the specified managed entity itself
     *  - self:     Returns events that pertain only to the specified managed
     *              entity, and not its children
     *
     * @var string EventFilterSpecRecursionOption enum
     */
    public $recursion;
}
