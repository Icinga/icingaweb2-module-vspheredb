<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use dipl\Html\Html;
use dipl\Html\Link;
use dipl\Translation\TranslationHelper;
use dipl\Web\Widget\NameValueTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Widget\SimpleUsageBar;
use Icinga\Module\Vspheredb\Web\Widget\SpectreMelddownBiosInfo;
use Icinga\Util\Format;

class HostInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var HostSystem */
    protected $host;

    /** @var PathLookup */
    protected $pathLookup;

    public function __construct(HostSystem $host, PathLookup $loopup)
    {
        $this->host = $host;
        $this->pathLookup = $loopup;
    }

    protected function getDb()
    {
        return $this->host->getConnection();
    }

    protected function assemble()
    {
        $host = $this->host;
        $uuid = $host->get('uuid');
        $lookup = $this->pathLookup;

        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($uuid, false)) as $parentUuid => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/hosts',
                ['uuid' => bin2hex($parentUuid)],
                ['data-base-target' => '_main']
            ));
        }

        $this->addNameValuePairs([
            $this->translate('UUID')         => $host->get('sysinfo_uuid'),
            $this->translate('API Version')  => $host->get('product_api_version'),
            $this->translate('Product Name') => $host->get('product_full_name'),
            $this->translate('CPU Usage')    => $this->showCpuUsage($host),
            $this->translate('Memory')       => $this->getFormattedMemory(),
            $this->translate('Path')         => $path,
            $this->translate('Power')        => $host->get('runtime_power_state'),
            $this->translate('Uptime')       => DateFormatter::formatDuration($host->quickStats()->get('uptime')),
            $this->translate('BIOS Version') => new SpectreMelddownBiosInfo($host),
            // $this->translate('BIOS Release Date') => $vm->get('bios_release_date'),
            $this->translate('Vendor')       => $host->get('sysinfo_vendor'),
            $this->translate('Model')        => $host->get('sysinfo_model'),
            $this->translate('Service Tag')  => $this->getFormattedServiceTag($host),
            $this->translate('CPU Model')    => $host->get('hardware_cpu_model'),
            $this->translate('CPU Packages') => $host->get('hardware_cpu_packages'),
            $this->translate('CPU Cores')    => $host->get('hardware_cpu_cores'),
            $this->translate('CPU Threads')  => $host->get('hardware_cpu_threads'),
            $this->translate('HBAs')         => $host->get('hardware_num_hba'),
            $this->translate('NICs')         => $host->get('hardware_num_nic'),
            $this->translate('Vms')          => Link::create(
                $host->countVms(),
                'vspheredb/host/vms',
                ['uuid' => bin2hex($uuid)]
            ),
        ]);
    }

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

    protected function showCpuUsage(HostSystem $host)
    {
        $total = $host->get('hardware_cpu_cores') * $host->get('hardware_cpu_mhz');
        $used = $host->quickStats()->get('overall_cpu_usage');
        $title = sprintf('Used %s / %s MHz', $used, $total);

        return [new SimpleUsageBar($used, $total, $title), ' ' . $title];
    }

    protected function getFormattedMemory()
    {
        $size = $this->host->get('hardware_memory_size_mb') * 1024 * 1024;
        $used = $this->host->quickStats()->get('overall_memory_usage_mb') * 1024 * 1024;
        $title = sprintf(
            'Used %s / %s',
            Format::bytes($used),
            Format::bytes($size)
        );
        $bar = new SimpleUsageBar($used, $size, $title);

        return [$bar, ' ' . $title];
    }
}
