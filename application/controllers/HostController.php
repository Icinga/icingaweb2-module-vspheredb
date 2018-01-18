<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Object\HostInfoTable;
use Icinga\Module\Vspheredb\Web\Table\VmsOnHostTable;
use dipl\Html\Link;

class HostController extends Controller
{
    /** @var HostSystem */
    protected $host;

    public function init()
    {
        $id = (int) $this->params->getRequired('id');
        $this->host = HostSystem::load($id, $this->db());
        $this->addTitle($this->host->object()->get('object_name'));

        $this->tabs()->add('index', [
            'label' => $this->translate('Host System'),
            'url' => 'vspheredb/host',
            'urlParams' => ['id' => $id]
        ])->add('vms', [
            'label' => sprintf(
                $this->translate('Virtual Machines (%d)'),
                $this->host->countVms()
            ),
            'url' => 'vspheredb/host/vms',
            'urlParams' => ['id' => $id]
        ])->activate($this->getRequest()->getActionName());
    }

    public function indexAction()
    {
        $table = new HostInfoTable($this->host, $this->pathLookup());
        $this->content()->add($table);
    }

    public function vmsAction()
    {
        $this->addLinkBackToHost();
        VmsOnHostTable::create($this->host)->renderTo($this);
    }

    protected function addLinkBackToHost()
    {
        $this->actions()->add(
            Link::create(
                $this->translate('Back to Host'),
                'vspheredb/host',
                ['id' => $this->host->get('id')],
                ['class' => 'icon-left-big']
            )
        );
    }
}
