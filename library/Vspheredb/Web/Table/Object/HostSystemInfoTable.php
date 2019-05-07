<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Web\Widget\SpectreMelddownBiosInfo;
use ipl\Html\Html;

class HostSystemInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var HostSystem */
    protected $host;

    public function __construct(HostSystem $host)
    {
        $this->host = $host;
    }

    protected function assemble()
    {
        $host = $this->host;

        $this->addNameValuePairs([
            $this->translate('Vendor / Model') => Html::sprintf(
                '%s %s (%s)',
                $host->get('sysinfo_vendor'),
                $host->get('sysinfo_model'),
                $this->getFormattedServiceTag($host)
            ),
            $this->translate('BIOS Version') => new SpectreMelddownBiosInfo($host),
            $this->translate('Uptime')       => DateFormatter::formatDuration($host->quickStats()->get('uptime')),
            $this->translate('System UUID')  => $host->get('sysinfo_uuid'),
        ]);
    }

    /**
     * @param HostSystem $host
     * @return \ipl\Html\HtmlElement|mixed
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
