<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Table\NameValueTable;
use gipfl\Web\Widget\Hint;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Web\Widget\BiosInfo;
use Icinga\Module\Vspheredb\Web\Widget\Link\Html5UiLink;
use Icinga\Module\Vspheredb\Web\Widget\Link\MobLink;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;

class HostSystemInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var HostSystem */
    protected $host;

    /** @var HostQuickStats */
    protected $quickStats;

    /** @var VCenter */
    protected $vCenter;

    public function __construct(HostSystem $host, HostQuickStats $quickStats, VCenter $vCenter)
    {
        $this->host = $host;
        $this->quickStats = $quickStats;
        $this->vCenter = $vCenter;
    }

    protected function assemble()
    {
        $this->prepend(new SubTitle($this->translate('System Information'), 'host'));
        $host = $this->host;

        $this->addNameValuePairs([
            $this->translate('Tools') => $this->prepareTools($host),
            $this->translate('Vendor') => $host->get('sysinfo_vendor'),
            $this->translate('Model') =>  $this->renderVendorModel(
                $host->get('sysinfo_vendor'),
                $host->get('sysinfo_model')
            ),
            $this->translate('Service Tag')  => $this->getFormattedServiceTag($host),
            $this->translate('BIOS Version') => new BiosInfo($host),
            $this->translate('Uptime')       => DateFormatter::formatDuration($this->quickStats->get('uptime')),
            $this->translate('System UUID')  => Html::tag('pre', $host->get('sysinfo_uuid')),
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

    protected function prepareTools(HostSystem $host)
    {
        $tools = [];

        if ($this->vCenter->getFirstServer(false, false) === null) {
            return Hint::warning($this->translate('There is no configured connection for this vCenter'));
        }
        if (\version_compare($this->vCenter->get('api_version'), '6.5', '>=')) {
            $tools[] = new Html5UiLink($this->vCenter, $host, 'HTML5 UI');
            $tools[] = ' ';
        }
        $tools[] = new MobLink($this->vCenter, $host, 'MOB');

        return $tools;
    }

    protected function renderVendorModel($vendor, $model)
    {
        $images = include __DIR__ . '/known-vendor-model-images.php';
        if (isset($images[$vendor][$model])) {
            $url = $images[$vendor][$model];
            $baseUrl = parse_url($url, PHP_URL_HOST);
            $img = Html::tag('img', [
                'src' => $images[$vendor][$model],
                'referrerpolicy' => 'no-referrer',
                'alt'   => $model,
                'title' => "$model - ",
                'style' => 'max-width: 100%'
            ]);

            return [$model, Html::tag('br'), Html::tag('br'), $img, Html::tag('br'), Html::tag('div', [
                'style' => 'text-align: right',
            ], "© $vendor (https://$baseUrl)")];
        }

        return $model;
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
