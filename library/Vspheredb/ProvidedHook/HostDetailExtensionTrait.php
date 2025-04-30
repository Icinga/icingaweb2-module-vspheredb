<?php

namespace Icinga\Module\Vspheredb\ProvidedHook;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Host as IcingaDBHost;
use Icinga\Module\Monitoring\Object\Host as MonitoringHost;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\CheckRelatedLookup;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Widget\CpuAbsoluteUsage;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

trait HostDetailExtensionTrait
{
    use Translation;

    protected Db $db;

    protected CheckRelatedLookup $lookup;

    /**
     * @param object<IcingaDBHost|MonitoringHost> $host
     * @param string $customVar
     *
     * @return ?string
     */
    abstract protected function getCustomVar(object $host, string $customVar): ?string;

    public function init()
    {
        $this->db = Db::newConfiguredInstance();
        $this->lookup = new CheckRelatedLookup($this->db);
    }

    /**
     * @param HostSystem|VirtualMachine $vObject
     *
     * @return ValidHtml
     */
    public function renderVObject($vObject): ValidHtml
    {
        $container = new HtmlElement('div', new Attributes([
            'class' => [
                'icinga-module',
                'module-vspheredb',
                'vspheredb-monitoring-integration'
            ]
        ]));

        switch (true) {
            case $vObject instanceof HostSystem:
                $container->addHtml($this->renderHostSystem($vObject));
                break;
            case $vObject instanceof VirtualMachine:
                $container->addHtml($this->renderVirtualMachine($vObject));
                break;
        }

        return $container;
    }

    protected function renderHostSystem(HostSystem $host): ValidHtml
    {
        $stats = HostQuickStats::loadFor($host);

        return (new HtmlDocument())
            ->addHtml(new HtmlElement(
                'h2',
                null,
                new Link(
                    sprintf($this->translate('ESXi Host: %s'), $host->object()->get('object_name')),
                    Url::fromPath('vspheredb/host', ['uuid' => Util::niceUuid($host->uuid)])
                )
            ))
            ->addHtml(new HtmlElement(
                'div',
                new Attributes(['class' => 'monitoring-integration-details']),
                new MemoryUsage($stats->get('overall_memory_usage_mb'), $host->get('hardware_memory_size_mb')),
                new CpuAbsoluteUsage($stats->get('overall_cpu_usage'), $host->get('hardware_cpu_cores'))
            ));
    }

    protected function renderVirtualMachine(VirtualMachine $vm): ValidHtml
    {
        $stats = VmQuickStats::loadFor($vm);

        return (new HtmlDocument())
            ->addHtml(new HtmlElement(
                'h2',
                null,
                new Link(
                    sprintf($this->translate('Virtual Machine: %s'), $vm->object()->get('object_name')),
                    Url::fromPath('vspheredb/vm', ['uuid' => Util::niceUuid($vm->uuid)])
                )
            ))
            ->addHtml(new HtmlElement(
                'div',
                new Attributes(['class' => 'monitoring-integration-details']),
                new MemoryUsage(
                    $stats->get('guest_memory_usage_mb'),
                    $vm->get('hardware_memorymb'),
                    $stats->get('host_memory_usage_mb')
                ),
                new CpuAbsoluteUsage($stats->get('overall_cpu_usage')) // $vm->get('hardware_numcpu')
            ));
    }

    /**
     * @param object<IcingaDBHost|MonitoringHost> $host
     * @param string $sourceType
     *
     * @return HostSystem|VirtualMachine|null
     */
    protected function find(object $host, string $sourceType)
    {
        $spec = [
            'HostSystem'     => ['host', 'host_system', 'host'],
            'VirtualMachine' => ['vm', 'virtual_machine', 'vm_host']
        ];

        $connections = $this->db->getDbAdapter()->fetchAll(
            $this->db->getDbAdapter()->select()->from('monitoring_connection')
                ->where('source_type = ?', $sourceType)
                ->order('priority DESC')
        );

        foreach ($connections as $connection) {
            foreach ($spec as $type => [$prefix, $filterPrefix, $monitoringPrefix]) {
                $filter = [];

                $property = $connection->{"monitoring_{$monitoringPrefix}_property"};
                if (! $property) {
                    continue;
                }

                if (substr($property, 0, 5) === 'vars.') {
                    $value = $this->getCustomVar($host, substr($property, 5));
                } else {
                    $value = $host->$property;
                }
                if (! $value) {
                    continue;
                }
                $filter[$connection->{"{$prefix}_property"}] = $value;

                if ($connection->vcenter_uuid !== null) {
                    $filter["$filterPrefix.vcenter_uuid"] = $connection->vcenter_uuid;
                }

                try {
                    $object = $this->lookup->findOneBy($type, $filter);
                    assert($object instanceof HostSystem || $object instanceof VirtualMachine);
                } catch (NotFoundError $_) {
                    continue;
                }

                return $object;
            }
        }

        return null;
    }
}
