<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\Json\JsonSerialization;

class ResourceUsage implements JsonSerialization
{
    public $usedMhz;
    public $totalMhz;
    public $usedMb;
    public $totalMb;
    public $dsCapacity;
    public $dsFreeSpace;
    public $dsUncommitted;

    public static function fromSerialization($any)
    {
        $self = new static();
        $self->usedMhz       = $any->used_mhz;
        $self->totalMhz      = $any->total_mhz;
        $self->usedMb        = $any->used_mb;
        $self->totalMb       = $any->total_mb;
        $self->dsCapacity    = $any->ds_capacity;
        $self->dsFreeSpace   = $any->ds_free_space;
        $self->dsUncommitted = $any->ds_uncommitted;

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'used_mhz'        => $this->usedMhz,
            'total_mhz'       => $this->totalMhz,
            'used_mb'         => $this->usedMb,
            'total_mb'        => $this->totalMb,
            'ds_capacity'     => $this->dsCapacity,
            'ds_free_space'   => $this->dsFreeSpace,
            'ds_uncommitted'  => $this->dsUncommitted,
        ];
    }
}
