<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Form\VCenterServerForm;
use Icinga\Module\Vspheredb\Web\Table\Objects\VCenterServersTable;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\Web\Widget\VCenterSummaries;
use Icinga\Web\Notification;

class VcenterController extends Controller
{
    public function indexAction()
    {
        $hexUuid = $this->params->getRequired('vcenter');
        $vCenter = VCenter::load(hex2bin($hexUuid), $this->db());
        $this->tabs()->add('vcenter', [
            'label' => $this->translate('vCenter'),
            'url'   => 'vspheredb/vcenter',
            'urlParams' => ['uuid' => $hexUuid]
        ])->add('perfcounters', [
            'label' => $this->translate('Counters'),
            'url'   => 'vspheredb/perfdata/counters',
            'urlParams' => ['uuid' => $hexUuid]
        ])->activate('vcenter');
        $this->setAutorefreshInterval(10);
        // $this->content()->add(new VCenterSyncInfo($vCenter));
        $this->content()->add(new VCenterSummaries($vCenter));
    }

    /**
     * @throws \Icinga\Security\SecurityException
     */
    public function serversAction()
    {
        $this->assertPermission('vspheredb/admin');
        $this->setAutorefreshInterval(10);
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

    /**
     * @throws \Icinga\Security\SecurityException
     */
    public function serverAction()
    {
        $this->assertPermission('vspheredb/admin');
        $this->addSingleTab($this->translate('vCenter Server'));
        $form = new VCenterServerForm(Db::newConfiguredInstance());
        if ($id = $this->params->get('id')) {
            $form->setObject(VCenterServer::loadWithAutoIncId($id, $this->db()));
            $this->addTitle($form->getObject()->get('host'));
        } else {
            $this->addTitle($this->translate('Create a new vCenter/ESXi-Connection'));
        }

        $form->on(VCenterServerForm::ON_SUCCESS, function (VCenterServerForm $form) {
            $object = $form->getObject();
            if ($object->hasBeenModified()) {
                $msg = sprintf(
                    $object->hasBeenLoadedFromDb()
                        ? $this->translate('The Connection has successfully been stored')
                        : $this->translate('A new Connection has successfully been created')
                );
                $object->store();
            } else {
                $msg = $this->translate('No action taken, object has not been modified');
            }
            Notification::success($msg);
            $this->redirectNow('vspheredb/vcenter/servers');
        });
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
    }

    protected function handleTabs()
    {
        $action = $this->getRequest()->getActionName();
        $tabs = $this->tabs(new MainTabs($this->Auth(), $this->db()));
        if ($tabs->has($action)) {
            $tabs->activate($action);
        } else {
            $this->redirectNow('vspheredb/configuration');
        }
    }
}
