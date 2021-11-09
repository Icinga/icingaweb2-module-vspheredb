<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

use RuntimeException;

abstract class DefaultPerformanceSet implements PerformanceSet
{
    /** @var string Name for this Performance Set */
    protected $name;

    /** @var string vmWare Object Type */
    protected $objectType;

    /** @var string vmWare Counters Group */
    protected $countersGroup;

    /** @var string[] Required counters by name */
    protected $counters;

    public function getName()
    {
        if ($this->name === null) {
            throw $this->missingPropertyError('name');
        }

        return $this->name;
    }

    public function getObjectType()
    {
        if ($this->objectType === null) {
            throw $this->missingPropertyError('objectType');
        }

        return $this->objectType;
    }

    public function getCountersGroup()
    {
        if ($this->countersGroup === null) {
            throw $this->missingPropertyError('countersGroup');
        }

        return $this->countersGroup;
    }

    public function getCounters()
    {
        if ($this->counters === null) {
            throw $this->missingPropertyError('counters');
        }

        return $this->counters;
    }

    /**
     * @param $property
     * @return RuntimeException
     */
    protected function missingPropertyError($property)
    {
        return new RuntimeException(sprintf(
            '$%s is required when extending %s, missing in %s',
            $property,
            __CLASS__,
            get_class($this)
        ));
    }
}
