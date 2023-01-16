<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use gipfl\Json\JsonSerialization;

/**
 *
 * https://pubs.vmware.com/vsphere-6-5/topic/com.vmware.wssdk.apiref.doc/vim.PerformanceManager.MetricSeriesCSV.html
 */
#[\AllowDynamicProperties]
class PerfMetricSeriesCSV implements JsonSerialization
{
    /** @var PerfMetricId */
    public $id;

    /** @var string */
    public $value;

    public static function fromSerialization($any)
    {
        $self = new static();
        $self->id = $any->id;
        $self->value = $any->value;

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) [
            'id'    => $this->id,
            'value' => $this->value,
        ];
    }
}
