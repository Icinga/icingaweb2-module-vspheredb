<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * The SelectionSpec is the base type for data object types that specify what
 * additional objects to filter.
 *
 * The base type contains only an optional "name" field, which allows a
 * selection to be named for future reference.
 *
 * More information is available in the subtype.
 *
 * Named selections support recursive specifications on an object hierarchy.
 * When used by a derived object, the "name" field allows other SelectionSpec
 * objects to refer to the object by name. When used as the base type only,
 * the "name" field indicates recursion to the derived object by name.
 *
 * Names are meaningful only within the same FilterSpec.
 */
class SelectionSpec
{
    /** @var ?string Name of the selection specification */
    public $name;

    /**
     * @param string $name
     * @return static
     */
    public static function reference($name)
    {
        $self = new static();
        $self->name = $name;
        return $self;
    }
}
