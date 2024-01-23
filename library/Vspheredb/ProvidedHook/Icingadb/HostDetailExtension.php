<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Icingadb;

use Icinga\Module\Icingadb\Hook\HostDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\IcingadbIntegration\IcingadbObjectFinder;
use Icinga\Module\Vspheredb\MonitoringIntegration\MonitoredObjectFinder;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Widget\CpuAbsoluteUsage;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use ipl\Html\Html;
use gipfl\IcingaWeb2\Link;
use ipl\Html\ValidHtml;

class HostDetailExtension extends HostDetailExtensionHook
{
    /** @var IcingadbObjectFinder */
    protected $finder;

    public function init()
    {
        $this->finder = new IcingadbObjectFinder(Db::newConfiguredInstance());
    }

    public function getHtmlForObject(Host $object) :ValidHtml
    {

        $vObject = $this->finder->find($object);
        if ($vObject instanceof HostSystem) {
            $container = $this->container();
            $container->add($this->renderHostSystem($vObject, $object));
            return $container;
        }
        if ($vObject instanceof VirtualMachine) {
            $container = $this->container();
            $container->add($this->renderVirtualMachine($vObject, $object));
            return $container;
        }

        return Html::tag('a');
    }

    protected function container()
    {
        return Html::tag('div', ['class' => [
            'icinga-module',
            'module-vspheredb',
            'vspheredb-monitoring-integration'
        ]]);
    }

    protected function renderHostSystem(HostSystem $host, Host $object)
    {
        $stats = HostQuickStats::loadFor($host);
        $cpu = new CpuAbsoluteUsage($stats->get('overall_cpu_usage'), $host->get('hardware_cpu_cores'));
        $mem = new MemoryUsage($stats->get('overall_memory_usage_mb'), $host->get('hardware_memory_size_mb'));

        return [
            Html::tag('h2', null, $this->linkToObject($host, $object)),
            Html::tag('div', ['class' => 'monitoring-integration-details'])->add([$mem, $cpu]),
        ];
    }

    protected function renderVirtualMachine(VirtualMachine $vm, Host $object)
    {
        $stats = VmQuickStats::loadFor($vm);
        $cpu = new CpuAbsoluteUsage($stats->get('overall_cpu_usage')); // $vm->get('hardware_numcpu')
        $mem = new MemoryUsage(
            $stats->get('guest_memory_usage_mb'),
            $vm->get('hardware_memorymb'),
            $stats->get('host_memory_usage_mb')
        );

        return [
            Html::tag('h2', null, $this->linkToObject($vm, $object)),
            Html::tag('div', ['class' => 'monitoring-integration-details'])->add([$mem, $cpu]),
        ];
    }

    protected function linkToObject($vObject, Host $object)
    {
        if ($vObject instanceof HostSystem) {
            $label = mt('vspheredb', 'ESXi Host: %s');
            $url = 'vspheredb/host';
        } elseif ($vObject instanceof VirtualMachine) {
            $label = mt('vspheredb', 'Virtual Machine: %s');
            $url = 'vspheredb/vm';
        } else {
            throw new \RuntimeException(sprintf('Unable to link to %s', get_class($vObject)));
        }

        return Link::create(
            sprintf($label, $vObject->object()->get('object_name')),
            $url,
            ['uuid' => Util::niceUuid($vObject->uuid), 'monitoringObject' => $object->name]
        );
    }
}
