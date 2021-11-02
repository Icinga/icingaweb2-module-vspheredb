<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

/**
 * Within a PropertyFilterSpec, the ObjectSpec data object type specifies the
 * managed object at which the filter begins to collect the managed object
 * references and properties specified by the associated PropertySpec set.
 *
 * If the "skip" property is present and set to true, then the filter does not
 * check to see if the starting object's type matches any of the types listed
 * in the associated sets of PropertySpec data objects.
 *
 * If the selectSet property is present, then this specifies additional objects
 * to filter. These objects are defined by one or more SelectionSpec objects.
 */
class ObjectSpec
{
    /**
     * Starting object
     *
     * Required privilege: System.View
     *
     * @var ManagedObjectReference
     */
    public $obj;

    /**
     * Set of selections to specify additional objects to filter
     *
     * @var ?SelectionSpec[]
     */
    public $selectSet;

    /**
     * Flag to specify whether to report this managed object's properties.
     * If the flag is true, the filter will not report this managed object's
     * properties.
     *
     * @var ?boolean
     */
    public $skip;

    /**
     * @param ManagedObjectReference $obj
     * @param ?SelectionSpec[] $selectSet
     * @param ?boolean $skip
     * @return static
     */
    public static function create(ManagedObjectReference $obj, array $selectSet = null, $skip = null)
    {
        $self = new static();
        $self->obj = $obj;
        $self->selectSet = $selectSet;
        $self->skip = $skip;

        return $self;
    }
}
