<?php

namespace Icinga\Module\Vspheredb\Polling\PerformanceSet;

use RuntimeException;

abstract class DefaultPerformanceSet implements PerformanceSet
{
    /** @var ?string Name for this Performance Set */
    protected ?string $name = null;

    /** @var ?string vmWare Object Type */
    protected ?string $objectType = null;

    /** @var ?string vmWare Counters Group */
    protected ?string $countersGroup = null;

    /** @var ?string[] Required counters by name */
    protected ?array $counters = null;

    public function getName(): string
    {
        if ($this->name === null) {
            throw $this->missingPropertyError('name');
        }

        return $this->name;
    }

    public function getObjectType(): string
    {
        if ($this->objectType === null) {
            throw $this->missingPropertyError('objectType');
        }

        return $this->objectType;
    }

    public function getCountersGroup(): string
    {
        if ($this->countersGroup === null) {
            throw $this->missingPropertyError('countersGroup');
        }

        return $this->countersGroup;
    }

    public function getCounters(): array
    {
        if ($this->counters === null) {
            throw $this->missingPropertyError('counters');
        }

        return $this->counters;
    }

    /**
     * @param $property
     *
     * @return RuntimeException
     */
    protected function missingPropertyError($property): RuntimeException
    {
        return new RuntimeException(sprintf(
            '$%s is required when extending %s, missing in %s',
            $property,
            __CLASS__,
            get_class($this)
        ));
    }
}
