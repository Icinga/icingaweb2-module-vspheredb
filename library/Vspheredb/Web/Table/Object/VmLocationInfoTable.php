<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Table\NameValueTable;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Format;
use Icinga\Module\Vspheredb\Hint\ConnectionStateDetails;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Widget\CpuUsage;
use Icinga\Module\Vspheredb\Web\Widget\Link\VCenterLink;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Web\Widget\Renderer\PathToObjectRenderer;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;

class VmLocationInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    /** @var VCenter */
    protected $vCenter;

    public function __construct(VirtualMachine $vm, VCenter $vCenter)
    {
        $this->prepend(new SubTitle($this->translate('Location'), 'home'));
        $this->vm = $vm;
        $this->vCenter = $vCenter;
    }

    protected function getDb()
    {
        return $this->vm->getConnection();
    }

    protected function assemble()
    {
        $vm = $this->vm;
        /** @var \Icinga\Module\Vspheredb\Db $connection */
        $connection = $vm->getConnection();
        $lookup =  new PathLookup($connection->getDbAdapter());
        $hostUuid = $vm->get('runtime_host_uuid');
        if ($hostUuid === null) {
            $hostInfo = '-';
            $hostResources = '-';
        } else {
            try {
                $host = HostSystem::load($hostUuid, $connection);
                $quickStats = HostQuickStats::loadFor($host);
                $hostInfo = [
                    $lookup->linkToObject($hostUuid),
                    Html::tag('br'),
                    ConnectionStateDetails::getFor($vm->get('connection_state')),
                ];
                $hostResources = $this->prepareHostInfo($host, $quickStats);
            } catch (NotFoundError $e) {
                $hostResources = '-';
                $hostInfo = Html::tag('span', [
                    'class' => 'error'
                ], $this->translate('Failed to load related host'));
            }
        }

        $this->addNameValuePairs([
            $this->translate('Host') => $hostInfo,
            // Better: Host CPU / Host Memory
            $this->translate('Host Resources') => $hostResources,
            $this->translate('Resource Pool') => $lookup->linkToObject($vm->get('resource_pool_uuid')),
            $this->translate('Path') => PathToObjectRenderer::render($vm),
            $this->translate('vCenter') => new VCenterLink($this->vCenter),
        ]);
    }

    protected function prepareHostInfo(HostSystem $host, HostQuickStats $quickStats)
    {
        $cpuCapacity = $host->get('hardware_cpu_cores') * $host->get('hardware_cpu_mhz');
        $cpuUsed = $quickStats->get('overall_cpu_usage');
        $memCapacity = $host->get('hardware_memory_size_mb');
        $memUsed = $quickStats->get('overall_memory_usage_mb');

        return Html::tag('div', [
            'class' => 'resource-info-small'
        ], Html::tag('div', [
            new CpuUsage($cpuUsed, $cpuCapacity),
            \sprintf(
                $this->translate('Free CPU: %s'),
                Format::mhz($cpuCapacity - $cpuUsed)
            ),
            Html::tag('br'),
            new MemoryUsage($memUsed, $memCapacity),
            \sprintf(
                $this->translate('Free Memory: %s'),
                Format::mBytes($memCapacity - $memUsed)
            ),
        ]));
    }
}
