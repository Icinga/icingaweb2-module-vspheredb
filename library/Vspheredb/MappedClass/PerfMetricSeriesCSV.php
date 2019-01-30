<?php

namespace Icinga\Module\Vspheredb\MappedClass;

/**
 *
 * https://pubs.vmware.com/vsphere-6-5/topic/com.vmware.wssdk.apiref.doc/vim.PerformanceManager.MetricSeriesCSV.html
 */
class PerfMetricSeriesCSV
{
    /** @var PerfMetricId */
    public $id;

    /** @var string */
    public $value;
}
