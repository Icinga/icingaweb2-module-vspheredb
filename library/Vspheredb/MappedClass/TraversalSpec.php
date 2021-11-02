<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * The TraversalSpec data object type specifies how to derive a new set of
 * objects to add to the filter.
 *
 * It specifies a property path whose value is either another managed object
 * or an array of managed objects included in the set of objects for
 * consideration.
 *
 * This data object can also be named, using the "name" field in the base type.
 */
class TraversalSpec extends SelectionSpec
{
    /** @var string Name of the property to use in order to select additional objects */
    public $path;

    /** @var ?SelectionSpec[] Optional set of selections to specify additional objects to filter */
    public $selectSet;

    /** @var boolean Flag to indicate whether to filter the object in the "path" field */
    public $skip;

    /** @var string Name of the object type containing the property (Alarm, Datastore, VirtualMachine...) */
    public $type;

    /**
     * @param string $name
     * @param string $type
     * @param string $path
     * @param ?SelectionSpec[] $selectSet
     * @param ?boolean $skip
     * @return static
     */
    public static function create($name, $type, $path, array $selectSet = null, $skip = null)
    {
        $self = new static();
        $self->name = $name;
        $self->type = $type;
        $self->path = $path;
        $self->selectSet = $selectSet;
        $self->skip = $skip;

        return $self;
    }
}
