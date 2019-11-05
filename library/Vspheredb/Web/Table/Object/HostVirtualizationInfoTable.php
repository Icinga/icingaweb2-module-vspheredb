<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\PathLookup;
use ipl\Html\Html;

class HostVirtualizationInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var HostSystem */
    protected $host;

    /** @var VCenter */
    protected $vCenter;

    /**
     * HostVirtualizationInfoTable constructor.
     * @param HostSystem $host
     * @throws NotFoundError
     */
    public function __construct(HostSystem $host)
    {
        $this->host = $host;
        $this->vCenter = VCenter::load($host->get('vcenter_uuid'), $host->getConnection());
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
            $this->translate('API Version')  => $host->get('product_api_version'),
            $this->translate('Hypervisor')   => $host->get('product_full_name'),
            $this->translate('MO Ref')       => $this->linkToVCenter($host->object()->get('moref')),
            $this->translate('Path')         => $path,
        ]);
    }

    /**
     * @param $moRef
     * @return \ipl\Html\HtmlElement|array
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
}
