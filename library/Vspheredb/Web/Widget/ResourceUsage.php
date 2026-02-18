<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\Json\JsonSerialization;
use ReturnTypeWillChange;

class ResourceUsage implements JsonSerialization
{
    public ?int $usedMhz = null;

    public ?int $totalMhz = null;

    public ?int $usedMb = null;

    public ?int $totalMb = null;

    public ?int $dsCapacity = null;

    public ?int $dsFreeSpace = null;

    public ?int $dsUncommitted = null;

    public static function fromSerialization($any): static
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

    #[ReturnTypeWillChange]
    public function jsonSerialize(): array
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
