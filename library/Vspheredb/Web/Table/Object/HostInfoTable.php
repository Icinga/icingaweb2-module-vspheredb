<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use dipl\Html\Html;
use dipl\Html\Icon;
use dipl\Html\Link;
use dipl\Translation\TranslationHelper;
use dipl\Web\Widget\NameValueTable;
use Icinga\Date\DateFormatter;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\MonitoringConnection;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Widget\CpuUsage;
use Icinga\Module\Vspheredb\Web\Widget\IcingaHostStatusRenderer;
use Icinga\Module\Vspheredb\Web\Widget\MemoryUsage;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Icinga\Module\Vspheredb\Web\Widget\PowerStateRenderer;
use Icinga\Module\Vspheredb\Web\Widget\SpectreMelddownBiosInfo;

class HostInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var HostSystem */
    protected $host;

    /** @var VCenter */
    protected $vCenter;

    public function __construct(HostSystem $host)
    {
        $this->host = $host;
        $this->vCenter = VCenter::load($host->get('vcenter_uuid'), $host->getConnection());
    }

    protected function getDb()
    {
        return $this->host->getConnection();
    }

    /**
     * @throws \Icinga\Exception\IcingaException
     */
    protected function assemble()
    {
        $host = $this->host;
        $uuid = $host->get('uuid');
        /** @var \Icinga\Module\Vspheredb\Db $connection */
        $connection = $host->getConnection();
        $lookup =  new PathLookup($connection);
        $powerStateRenderer = new PowerStateRenderer();
        $overallStatusRenderer = new OverallStatusRenderer();
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($uuid, false)) as $parentUuid => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/hosts',
                ['uuid' => bin2hex($parentUuid)],
                ['data-base-target' => '_main']
            ));
        }
        $this->addNameValueRow(
            $this->translate('Monitoring'),
            $this->getMonitoringInfo($host)
        );
        $this->addNameValuePairs([
            $this->translate('Status') => $overallStatusRenderer($host->object()->get('overall_status')),
            $this->translate('Power')  => $powerStateRenderer($host->get('runtime_power_state')),
        ]);

        $this->addNameValuePairs([
            $this->translate('CPU / Memory') => [
                Html::tag(
                    'div',
                    ['style' => 'width: 30%; display:inline-block; margin-right: 1em;'],
                    new CpuUsage(
                        $host->quickStats()->get('overall_cpu_usage'),
                        $host->get('hardware_cpu_cores') * $host->get('hardware_cpu_mhz')
                    )
                ),
                Html::tag(
                    'div',
                    ['style' => 'width: 30%; display:inline-block; margin-right: 1em;'],
                    new MemoryUsage(
                        $host->quickStats()->get('overall_memory_usage_mb'),
                        $host->get('hardware_memory_size_mb')
                    )
                ),
            ],
            $this->translate('UUID')         => $host->get('sysinfo_uuid'),
            $this->translate('API Version')  => $host->get('product_api_version'),
            $this->translate('Hypervisor')   => $host->get('product_full_name'),
            $this->translate('MO Ref')       => $this->linkToVCenter($host->object()->get('moref')),
            $this->translate('Path')         => $path,
            $this->translate('Uptime')       => DateFormatter::formatDuration($host->quickStats()->get('uptime')),
            $this->translate('BIOS Version') => new SpectreMelddownBiosInfo($host),
            // $this->translate('BIOS Release Date') => $vm->get('bios_release_date'),
            $this->translate('Vendor / Model')       => Html::sprintf(
                '%s %s (%s)',
                $host->get('sysinfo_vendor'),
                $host->get('sysinfo_model'),
                $this->getFormattedServiceTag($host)
            ),
            $this->translate('CPU')    => sprintf(
                $this->translate('%d Packages, %d Cores, %d Threads (%s)'),
                $host->get('hardware_cpu_packages'),
                $host->get('hardware_cpu_cores'),
                $host->get('hardware_cpu_threads'),
                $host->get('hardware_cpu_model')
            ),
            $this->translate('HBAs')         => $host->get('hardware_num_hba'),
            $this->translate('NICs')         => $host->get('hardware_num_nic'),
            $this->translate('Vms')          => Link::create(
                $host->countVms(),
                'vspheredb/host/vms',
                ['uuid' => bin2hex($uuid)]
            ),
        ]);
    }

    /**
     * @param HostSystem $host
     * @return array|null
     * @throws \Icinga\Exception\IcingaException
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function getMonitoringInfo(HostSystem $host)
    {
        $name = $host->get('host_name');
        $statusRenderer = new IcingaHostStatusRenderer();
        $monitoring = MonitoringConnection::eventuallyLoadForVCenter($this->vCenter);

        try {
            if ($monitoring && $monitoring->hasHost($name)) {
                $monitoringState = $monitoring->getHostState($name);
                return [
                    // TODO: is_acknowledged, is_in_downtime
                    $statusRenderer($monitoringState->current_state),
                    ' ',
                    $monitoringState->output,
                    ' ',
                    Link::create(
                        $this->translate('more'),
                        'monitoring/host/show',
                        ['host' => $name],
                        ['class' => 'icon-right-small']
                    )
                ];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return [
                Html::tag('p', ['class' => 'error'], sprintf(
                    $this->translate('Unable to check monitoring state: %s'),
                    $e->getMessage()
                ))
            ];
        }
    }

    /**
     * @param $moRef
     * @return \dipl\Html\HtmlElement|array
     */
    protected function linkToVCenter($moRef)
    {
        try {
            $server = $this->vCenter->getFirstServer();
        } catch (NotFoundError $e) {
            return [
                Icon::create('warning-empty', [
                    'class' => 'red'
                ]),
                ' ',
                $this->translate('No related vServer has been configured')
            ];
        }

        return Html::tag('a', [
            'href' => sprintf(
                'https://%s/mob/?moid=%s',
                $server->get('host'),
                rawurlencode($moRef)
            ),
            'target' => '_blank',
            'title' => $this->translate('Jump to the Managed Object browser')
        ], $moRef);
    }

    /**
     * @param HostSystem $host
     * @return \dipl\Html\HtmlElement|mixed
     */
    protected function getFormattedServiceTag(HostSystem $host)
    {
        if ($this->host->get('sysinfo_vendor') === 'Dell Inc.') {
            return $this->linkToDellSupport($host->get('service_tag'));
        } else {
            return $host->get('service_tag');
        }
    }

    protected function linkToDellSupport($serviceTag)
    {
        $urlPattern = 'http://www.dell.com/support/home/product-support/servicetag/%s/drivers';

        $url = sprintf(
            $urlPattern,
            strtolower($serviceTag)
        );

        return Html::tag(
            'a',
            [
                'href'   => $url,
                'target' => '_blank',
                'title'  => $this->translate('Dell Support Page'),
                'rel'    => 'noreferrer'
            ],
            $serviceTag
        );
    }
}
