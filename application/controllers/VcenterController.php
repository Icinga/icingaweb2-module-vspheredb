<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Html;
use dipl\Html\Link;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Form\VCenterServerForm;
use Icinga\Module\Vspheredb\Web\Table\Objects\VCenterServersTable;
use Icinga\Module\Vspheredb\Web\Widget\VCenterSummaries;
use Icinga\Module\Vspheredb\Web\Widget\VCenterSyncInfo;

class VcenterController extends Controller
{
    public function indexAction()
    {
        $this->setAutorefreshInterval(10);
        $this->handleTabs();
        $vCenters = VCenter::loadAll($this->db());
        if (empty($vCenters)) {
            $this->redirectNow('vspheredb/vcenter/servers');
        }
        foreach ($vCenters as $vCenter) {
            $this->content()->add(new VCenterSyncInfo($vCenter));
        }
        $this->content()->add([
            // new VCenterInfoTable($vCenter),
            new VCenterSummaries($vCenter),
        ]);
    }

    public function serversAction()
    {
        $this->handleTabs();
        $this->addTitle($this->translate('vCenter Servers'));
        $this->actions()->add(
            Link::create(
                $this->translate('Add'),
                'vspheredb/vcenter/server',
                null,
                [
                    'class' => 'icon-plus',
                    'data-base-target' => '_next'
                ]
            )
        );

        $table = new VCenterServersTable($this->db());
        $table->renderTo($this);
    }

    public function serverAction()
    {
        $this->addSingleTab($this->translate('vCenter Server'));

        $form = new VCenterServerForm();
        $form->setVsphereDb(Db::newConfiguredInstance());
        if ($id = $this->params->get('id')) {
            $form->loadObject($id);
            $this->addTitle($form->getObject()->get('host'));
        }
        $form->handleRequest();
        $this->content()->add(Html::tag(
            'div',
            ['class' => 'icinga-module module-director'],
            $form
        ));
    }

    protected function handleTabs()
    {
        $this->tabs()->add('index', [
            'label'     => $this->translate('vCenter Overview'),
            'url'       => 'vspheredb/vcenter',
        ])->add('servers', [
            'label'     => $this->translate('Servers'),
            'url'       => 'vspheredb/vcenter/servers',
        ])->activate($this->getRequest()->getActionName());
    }
}
