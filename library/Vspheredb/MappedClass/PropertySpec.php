<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * Within a PropertyFilterSpec, A PropertySpec specifies which properties
 * should be reported to the client for objects of the given managed object
 * type that are visited and not skipped. One more subtle side effect is that
 * if a managed object is visited and not skipped, but there is no PropertySpec
 * associated with the managed object's type, the managed object will not be
 * reported to the client.
 *
 * Also, the set of properties applicable to a given managed object type is the
 * union of the properties implied by the PropertySpec objects even, in the case
 * of a RetrieveResult, where there may be an applicable PropertySpec in more
 * than one filter.
 */
#[\AllowDynamicProperties]
class PropertySpec
{
    /**
     * Specifies whether all properties of the object are read. If this property
     * is set to true, the pathSet property is ignored.
     *
     * @var ?boolean
     */
    public $all;

    /**
     * Specifies which managed object properties are retrieved. If the pathSet
     * is empty, then the PropertyCollector retrieves references to the managed
     * objects and no managed object properties are collected.
     *
     * @var ?string[]
     */
    public $pathSet;

    /**
     * Name of the managed object type being collected
     *
     * e.g. Alarm, Datacenter, VirtualMachine
     *
     * @var string
     */
    public $type;

    /**
     * @param string $type
     * @param ?string[] $pathSet
     * @param ?boolean $all
     * @return static
     */
    public static function create($type, array $pathSet = null, $all = null)
    {
        $self = new static();
        $self->type = $type;
        $self->pathSet = $pathSet;
        $self->all = $all;

        return $self;
    }
}
