<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Data\Anonymizer;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class HostHeader extends BaseHtmlElement
{
    /** @var HostSystem */
    protected HostSystem $host;

    /** @var ?HtmlDocument */
    protected ?HtmlDocument $icons = null;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'host-header'];

    /** @var HostQuickStats */
    protected HostQuickStats $quickStats;

    public function __construct(HostSystem $host, HostQuickStats $quickStats)
    {
        $this->host = $host;
        $this->quickStats = $quickStats;
    }

    public function getIcons(): HtmlDocument
    {
        $this->icons ??= (new HtmlDocument())->add([
            (new OverallStatusRenderer())($this->host->object()->get('overall_status')),
            (new PowerStateRenderer())($this->host->get('runtime_power_state'))
        ]);

        return $this->icons;
    }

    /**
     * @throws NotFoundError
     */
    protected function assemble(): void
    {
        $host = $this->host;
        $host->object()->set('object_name', Anonymizer::anonymizeString($host->object()->get('object_name')));

        $cpu = new CpuAbsoluteUsage(
            $this->quickStats->get('overall_cpu_usage'),
            $host->get('hardware_cpu_cores')
        );
        $mem = new MemoryUsage(
            $this->quickStats->get('overall_memory_usage_mb'),
            $host->get('hardware_memory_size_mb')
        );
        $title = Html::tag('h1', [$host->object()->get('object_name'), $this->getIcons()]);
        $this->add([$cpu, $title, $mem]);
    }
}
