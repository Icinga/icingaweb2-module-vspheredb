<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class PerformanceManager
{
    /** @var PerformanceDescription */
    public $description;

    /** @var PerfInterval[]|null */
    public $historicalInterval;

    /** @var PerfCounterInfo[] */
    protected $perfCounter;

    public function getPerfCounter()
    {
        return $this->perfCounter;
    }

    // TODO: Verify whether this hack is still necessary
    public function __set($key, $value)
    {
        if ($key === 'perfCounter') {
            if (is_object($value) && isset($value->PerfCounterInfo)) {
                $this->perfCounter = $value->PerfCounterInfo;
            } else {
                $this->perfCounter = $value;
            }
        } else {
            $this->$key = $value;
        }
    }
}
