<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use gipfl\Json\JsonSerialization;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

class PerfEntityMetricCSV implements JsonSerialization
{
    /** @var ManagedObjectReference */
    public $entity;

    /** @var string */
    public $sampleInfoCSV;

    /** @var PerfMetricSeriesCSV[] */
    public $value = [];

    public static function fromSerialization($any)
    {
        $self = new static;
        $self->entity = ManagedObjectReference::fromSerialization($any->entity);
        $self->sampleInfoCSV = $any->sampleInfoCSV;
        foreach ($any->value as $value) {
            $self->value[] = PerfMetricSeriesCSV::fromSerialization($value);
        }

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) [
            'entity'        => $this->entity,
            'sampleInfoCSV' => $this->sampleInfoCSV,
            'value'         => $this->value,
        ];
    }
}
