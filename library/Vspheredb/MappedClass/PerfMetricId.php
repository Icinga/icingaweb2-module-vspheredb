<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class PerfMetricId
{
    /** @var int */
    public $counterId;

    /**
     * https://pubs.vmware.com/vsphere-6-5/topic/com.vmware.wssdk.apiref.doc/vim.PerformanceManager.MetricId.html
     *
     * From com.vmware.wssdk.apiref.doc/vim.PerformanceManager.MetricId:
     *
     * An identifier that is derived from configuration names for the device
     * associated with the metric. It identifies the instance of the metric
     * with its source. This property may be empty.
     *
     * - For memory and aggregated statistics, this property is empty.
     * - For host and virtual machine devices, this property contains the name
     *   of the device, such as the name of the host-bus adapter or the name of
     *   the virtual Ethernet adapter. For example, “mpx.vmhba33:C0:T0:L0” or
     *   “vmnic0:”
     * - For a CPU, this property identifies the numeric position within the CPU
     *   core, such as 0, 1, 2, 3.
     * - For a virtual disk, this property identifies the file type:
     *   - DISKFILE, for virtual machine base-disk files
     *   - SWAPFILE, for virtual machine swap files
     *   - DELTAFILE, for virtual machine snapshot overhead files
     *   - OTHERFILE, for all other files of a virtual machine
     *
     * @var string|null
     */
    public $instance;

    /**
     * PerfMetricId constructor.
     * @param $counterId
     * @param string $instance
     */
    public function __construct($counterId, $instance = null)
    {
        $this->counterId = (int) $counterId;
        if ($instance !== null) {
            $this->instance = (string) $instance;
        }
    }
}
