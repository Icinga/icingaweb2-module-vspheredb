<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class EventHistoryCollector
{
    /**
     * The filter used to create this collector
     *
     * The type of the returned filter is determined by the managed object for
     * which the collector is created
     *
     * @var mixed
     */
    public $filter;

    /**
     * The items in 'viewable latest page'. As new items are added to the
     * collector, they are appended at the end of the page. The oldest item is
     * removed from the collector whenever there are more items in the
     * collector than the maximum established by setLatestPageSize
     *
     * @var KnownEvent[]
     */
    public $latestPage = [];
}
