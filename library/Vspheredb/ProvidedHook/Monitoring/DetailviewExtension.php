<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Monitoring;

use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\MonitoringIntegration\MonitoredObjectFinder;
use Icinga\Module\Vspheredb\Web\Widget\CpuAbsoluteUsage;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use ipl\Html\Html;
use gipfl\IcingaWeb2\Link;

class DetailviewExtension extends DetailviewExtensionHook
{
    /** @var MonitoredObjectFinder */
    protected $finder;

    public function init()
    {
        $this->finder = new MonitoredObjectFinder(Db::newConfiguredInstance());
    }

    public function getHtmlForObject(MonitoredObject $object)
    {

        $vObject = $this->finder->find($object);
        if ($vObject instanceof HostSystem) {
            $container = $this->container();
            $container->add($this->renderHostSystem($vObject, $object));
            return $container->render();
        }
        if ($vObject instanceof VirtualMachine) {
            $container = $this->container();
            $container->add($this->renderVirtualMachine($vObject, $object));
            return $container->render();
        }

        return '';
    }

    protected function container()
    {
        return Html::tag('div', ['class' => [
            'icinga-module',
            'module-vspheredb',
            'vspheredb-monitoring-integration'
        ]]);
    }

    protected function renderHostSystem(HostSystem $host, MonitoredObject $object)
    {
        $stats = HostQuickStats::loadFor($host);
        $cpu = new CpuAbsoluteUsage($stats->get('overall_cpu_usage'), $host->get('hardware_cpu_cores'));
        $mem = new MemoryUsage($stats->get('overall_memory_usage_mb'), $host->get('hardware_memory_size_mb'));

        return [
            Html::tag('h2', null, $this->linkToObject($host, $object)),
            Html::tag('div', ['class' => 'monitoring-integration-details'])->add([$mem, $cpu]),
        ];
    }

    protected function renderVirtualMachine(VirtualMachine $vm, MonitoredObject $object)
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

    protected function linkToObject($vObject, MonitoredObject $object)
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
            ['uuid' => bin2hex($vObject->uuid), 'monitoringObject' => $object->name]
        );
    }
}
