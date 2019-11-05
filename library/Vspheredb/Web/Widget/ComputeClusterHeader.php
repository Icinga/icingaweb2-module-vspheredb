<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\DbObject\ComputeCluster;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class ComputeClusterHeader extends HtmlDocument
{
    /** @var ComputeCluster */
    protected $computeCluster;

    public function __construct(ComputeCluster $computeCluster)
    {
        $this->computeCluster = $computeCluster;
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function assemble()
    {
        $computeCluster = $this->computeCluster;
        $object = $computeCluster->object();
        $overallStatusRenderer = new OverallStatusRenderer();
        $icons = [
            $overallStatusRenderer($object->get('overall_status')),
        ];

        $stats = $computeCluster->calculateStats();

        $cpu = new CpuAbsoluteUsage(
            $stats->overall_cpu_usage,
            $stats->hardware_cpu_cores
        );
        $mem = new MemoryUsage(
            $stats->overall_memory_usage_mb,
            $stats->hardware_memory_size_mb
        );
        $title = Html::tag('h1', [
            $computeCluster->get('object_name'),
            $icons
        ]);
        $this->add([
            $cpu,
            $title,
            $mem
        ]);
    }
}
