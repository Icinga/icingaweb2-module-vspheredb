<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use ipl\Html\Html;

class ServiceTagRenderer extends Html
{
    use TranslationHelper;

    public function __invoke($host)
    {
        if (! $host instanceof  HostSystem) {
            $host = HostSystem::create([
                'service_tag'    => $host->service_tag,
                'sysinfo_vendor' => $host->sysinfo_vendor,
            ]);
        }

        return $this->getFormattedServiceTag($host);
    }

    protected function getFormattedServiceTag(HostSystem $host)
    {
        if ($host->get('sysinfo_vendor') === 'Dell Inc.') {
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

        return Html::tag('a', [
                'href'   => $url,
                'target' => '_blank',
                'title'  => $this->translate('Dell Support Page'),
                'rel'    => 'noreferrer'
        ], [Icon::create('forward'), $serviceTag]);
    }
}
