<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Object\HostInfoTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsTable;
use dipl\Html\Link;

class HostController extends Controller
{
    /** @var HostSystem */
    protected $host;

    public function init()
    {
        $hexId = $this->params->getRequired('uuid');
        $uuid = hex2bin($hexId);
        $this->host = HostSystem::load($uuid, $this->db());
        $this->addTitle($this->host->object()->get('object_name'));

        $this->tabs()->add('index', [
            'label' => $this->translate('Host System'),
            'url' => 'vspheredb/host',
            'urlParams' => ['uuid' => $hexId]
        ])->add('vms', [
            'label' => sprintf(
                $this->translate('Virtual Machines (%d)'),
                $this->host->countVms()
            ),
            'url' => 'vspheredb/host/vms',
            'urlParams' => ['uuid' => $hexId]
        ])->activate($this->getRequest()->getActionName());
    }

    public function indexAction()
    {
        $table = new HostInfoTable($this->host, $this->vCenter(), $this->pathLookup());
        $this->content()->add($table);
    }

    public function vmsAction()
    {
        $this->addLinkBackToHost();
        (new VmsTable($this->db()))->filterHost($this->host->get('uuid'))
            ->renderTo($this);
    }

    protected function addLinkBackToHost()
    {
        $this->actions()->add(
            Link::create(
                $this->translate('Back to Host'),
                'vspheredb/host',
                ['uuid' => bin2hex($this->host->get('uuid'))],
                ['class' => 'icon-left-big']
            )
        );
    }
}
