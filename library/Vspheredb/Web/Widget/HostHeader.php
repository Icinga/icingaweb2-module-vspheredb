<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\DbObject\HostSystem;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class HostHeader extends HtmlDocument
{
    /** @var HostSystem */
    protected $host;

    public function __construct(HostSystem $host)
    {
        $this->host = $host;
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function assemble()
    {
        $host = $this->host;
        $powerStateRenderer = new PowerStateRenderer();
        $overallStatusRenderer = new OverallStatusRenderer();
        $icons = [
            $overallStatusRenderer($host->object()->get('overall_status')),
            $powerStateRenderer($host->get('runtime_power_state')),
        ];

        $cpu = new CpuAbsoluteUsage(
            $host->quickStats()->get('overall_cpu_usage'),
            $host->get('hardware_cpu_cores')
        );
        $mem = new MemoryUsage(
            $host->quickStats()->get('overall_memory_usage_mb'),
            $host->get('hardware_memory_size_mb')
        );
        $title = Html::tag('h1', [
            $host->object()->get('object_name'),
            $icons
        ]);
        $this->add([
            $cpu,
            $title,
            $mem
        ]);
    }
}
