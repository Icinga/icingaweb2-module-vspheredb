<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 * Specify the property data that is included in a filter.
 *
 * A filter can specify part of a single managed object, or parts of multiple
 * related managed objects in an inventory hierarchy - for example, to collect
 * updates from all virtual machines in a given folder.
 */
class PropertyFilterSpec
{
    /**
     * Set of specifications that determine the objects to filter
     *
     * @var ObjectSpec[]
     */
    public $objectSet;

    /**
     * Set of properties to include in the filter, specified for each object type
     *
     * @var PropertySpec[]
     */
    public $propSet;

    /**
     * Control how to report missing objects during filter creation.
     *
     * If false or unset and objectSet refers to missing objects, filter
     * creation will fail with a ManagedObjectNotFound fault.
     *
     * If true and objectSet refers to missing objects, filter creation will
     * not fail and missing objects will be reported via filter results. This
     * is the recommended setting when objectSet refers to transient objects.
     *
     * In an UpdateSet missing objects will appear in the missingSet field.
     *
     * In a RetrieveResult missing objects will simply be omitted from the
     * objects field.
     *
     * For a call to RetrieveProperties missing objects will simply be omitted
     * from the results.
     *
     * Since vSphere API 4.1
     *
     * @var ?boolean
     */
    public $reportMissingObjectsInResults;

    /**
     * @param ObjectSpec[] $objectSet
     * @param PropertySpec[] $propSet
     * @param ?boolean $reportMissingObjectsInResults
     * @return static
     */
    public static function create(array $objectSet, array $propSet, $reportMissingObjectsInResults = null)
    {
        $self = new static();
        $self->objectSet = $objectSet;
        $self->propSet = $propSet;
        $self->reportMissingObjectsInResults = $reportMissingObjectsInResults;

        return $self;
    }
}
