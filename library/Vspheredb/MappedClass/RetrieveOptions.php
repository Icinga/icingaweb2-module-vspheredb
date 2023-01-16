<?php

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
class RetrieveOptions
{
    /**
     *  The maximum number of ObjectContent data objects that should be returned in a
     * single result from RetrievePropertiesEx.
     *
     * An unset value indicates that there is no maximum. In this case PropertyCollector
     * policy may still limit the number of objects. Any remaining objects may be retrieved
     * with ContinueRetrievePropertiesEx.
     *
     * A positive value causes RetrievePropertiesEx to suspend the retrieval when the count
     * of objects reaches the specified maximum. PropertyCollector policy may still limit
     * the count to something less than maxObjects. Any remaining objects may be
     * with ContinueRetrievePropertiesEx.
     *
     * A value less than or equal to 0 is illegal.
     *
     * @var ?int
     */
    public $maxObjects;

    /**
     * @param ?int $maxObjects
     * @return static
     */
    public static function create($maxObjects = null)
    {
        $self = new static();
        $self->maxObjects = $maxObjects;

        return $self;
    }
}
