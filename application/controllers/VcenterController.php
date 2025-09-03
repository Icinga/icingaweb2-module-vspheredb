<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\ZfDbStore\ZfDbStore;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenterServer;
use Icinga\Module\Vspheredb\Storable\PerfdataSubscription;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Form\DeleteVCenterForm;
use Icinga\Module\Vspheredb\Web\Form\VCenterForm;
use Icinga\Module\Vspheredb\Web\Form\VCenterServerForm;
use Icinga\Module\Vspheredb\Web\Form\VCenterShipMetricsForm;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\Web\Tabs\VCenterTabs;
use Icinga\Module\Vspheredb\Web\Widget\Link\MobLink;
use Icinga\Module\Vspheredb\Web\Widget\ResourceUsageLoader;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use Icinga\Module\Vspheredb\Web\Widget\UsageSummary;
use Icinga\Module\Vspheredb\Web\Widget\VCenterHeader;
use Icinga\Module\Vspheredb\Web\Widget\VCenterSummaries;
use Icinga\Web\Notification;
use Ramsey\Uuid\Uuid;

class VcenterController extends Controller
{
    use AsyncControllerHelper;
    use RpcServerUpdateHelper;

    public function indexAction()
    {
        $vCenter = $this->requireVCenter();
        $this->tabs(new VCenterTabs($vCenter))->activate('vcenter');
        $this->controls()->add(new VCenterHeader($vCenter));
        if ($this->hasPermission('vspheredb/admin')) {
            $this->actions()->add(Link::create(
                $this->translate('Edit'),
                'vspheredb/vcenter/edit',
                ['vcenter' => $this->params->get('vcenter')],
                ['class' => 'icon-edit']
            ));
        }
        $this->actions()->add(new MobLink($vCenter));
        $this->setAutorefreshInterval(10);
        // $this->content()->add(new VCenterSyncInfo($vCenter));
        $this->content()->add(new UsageSummary(
            (new ResourceUsageLoader($vCenter->getConnection()->getDbAdapter()))
                ->filterVCenterUuid(Uuid::fromBytes($this->requireVCenter()->getUuid()))
                ->fetch()
        ));
        $this->content()->add(new SubTitle($this->translate('Object Summaries')));
        $this->content()->add(new VCenterSummaries($vCenter));
    }

    public function editAction()
    {
        $this->assertPermission('vspheredb/admin');
        $vCenter = $this->requireVCenter();
        $this->tabs(new VCenterTabs($vCenter))->activate('vcenter');
        $this->controls()->add(new VCenterHeader($vCenter));
        $this->actions()->add(Link::create(
            $this->translate('Back'),
            'vspheredb/vcenter',
            ['vcenter' => $this->params->get('vcenter')],
            ['class' => 'icon-left-big']
        ));

        $success = function () use ($vCenter) {
            $msg = $vCenter->hasBeenModified()
                ? $this->translate('The vCenter has successfully been stored')
                : $this->translate('No action taken, vCenter has not been modified');
            Notification::success($msg);
            $this->redirectNow($this->getOriginalUrl());
        };

        $form = new VCenterForm($vCenter);
        $form->on(VCenterForm::ON_SUCCESS, $success);
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);

        $store = new ZfDbStore($this->db()->getDbAdapter());
        $form = new VCenterShipMetricsForm($store, $vCenter, $this->remoteClient(), $this->loop());
        if ($subscription = PerfdataSubscription::optionallyLoadForVCenter($vCenter, $store)) {
            $form->setObject($subscription);
        }
        $form->on(VCenterShipMetricsForm::ON_SUCCESS, function () {
            $this->redirectNow($this->getOriginalUrl());
        });
        $form->on(VCenterShipMetricsForm::ON_DELETE, function () {
            $this->redirectNow($this->getOriginalUrl());
        });
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);

        $form = new DeleteVCenterForm($this->db(), $vCenter, $this->remoteClient(), $this->loop());
        $form->on(DeleteVCenterForm::ON_SUCCESS, function () use ($vCenter) {
            $this->redirectNow('vspheredb/vcenters');
        });
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
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
                $msg .= '. ' . $this->sendServerInfoToSocket();
            } else {
                $msg = $this->translate('No action taken, object has not been modified');
            }
            Notification::success($msg);
            $this->redirectNow('vspheredb/configuration/servers');
        });
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
        if ($form->hasBeenDeleted()) {
            Notification::success($this->translate('The connection has been deleted'));
            $this->redirectNow('vspheredb/configuration/servers');
        }
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
