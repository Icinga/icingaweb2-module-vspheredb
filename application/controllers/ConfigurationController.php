<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\Web\Widget\Hint;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Polling\ApiConnection;
use Icinga\Module\Vspheredb\Web\Form\ChooseDbResourceForm;
use Icinga\Module\Vspheredb\Web\Form\MonitoringConnectionForm;
use Icinga\Module\Vspheredb\Web\Table\MonitoredObjectMappingTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\VCenterServersTable;
use Icinga\Module\Vspheredb\Web\Tabs\ConfigTabs;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Web\Notification;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;

class ConfigurationController extends Controller
{
    use AsyncControllerHelper;
    use RpcServerUpdateHelper;

    public function init()
    {
        $this->assertPermission('vspheredb/admin');
        parent::init();
    }

    public function databaseAction()
    {
        $this->addTitle($this->translate('vSphereDB Database Configuration'));
        $this->tabs(new ConfigTabs())->activate('database');
        $this->setAutorefreshInterval(10);
        $form = new ChooseDbResourceForm();
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
        if ($form->hasMessages()) {
            $this->addSingleTab($this->translate('Configuration'));

            return;
        }

        if ($this->Config()->get('db', 'resource')) {
            $db = $this->db();
            if ($db === null) {
                return;
            }
            $this->content()->add(Html::tag('br'));
            $migrations = Db::migrationsForDb($db);
            if (! $migrations->hasSchema()) {
                $this->content()->add(Hint::warning($this->translate(
                    'The database has no vSphereDB schema. Waiting for the Background Daemon'
                    . ' to initialize the database'
                )));
                return;
            }

            if ($migrations->hasPendingMigrations()) {
                $this->content()->add(Hint::warning($this->translate(
                    'The database has pending DB migrations. Please restart the Background'
                    . ' daemon to apply them'
                )));
                return;
            }

            // Obsolete:
            /*
            $migrations = new ProposeMigrations($db, $this->Auth(), $this->getServerRequest());
            if ($migrations->hasAppliedMigrations()) {
                $this->redirectNow($this->url());
            }
            $this->content()->add($migrations);
            */
        }
    }

    /**
     * @throws \Icinga\Security\SecurityException
     */
    public function serversAction()
    {
        $this->tabs(new ConfigTabs($this->db()))->activate('servers');
        $this->setAutorefreshInterval(10);
        $this->addTitle($this->translate('vCenter Servers'));
        $this->actions()->add(Link::create($this->translate('Add'), 'vspheredb/vcenter/server', null, [
            'class' => 'icon-plus',
            'data-base-target' => '_next'
        ]));
        try {
            $connections = $this->mapServerConnectionsToId($this->syncRpcCall('vsphere.getApiConnections'));
            foreach ($connections as $conns) {
                foreach ($conns as $conn) {
                    if (
                        in_array($conn->state, [
                        ApiConnection::STATE_INIT,
                        ApiConnection::STATE_LOGIN,
                        ])
                    ) {
                        $this->setAutorefreshInterval(5);
                    }
                }
            }
            $this->setAutorefreshInterval(5);
        } catch (\Exception $e) {
            $connections = null;
            $this->content()->add(
                Hint::warning($this->translate('Got no connection information. Is the Damon running?'))
            );
            $this->setAutorefreshInterval(5);
        }
        $table = new VCenterServersTable($this->db());
        $table->setServerConnections($connections);
        $table->setRequest($this->getServerRequest());
        $table->on(VCenterServersTable::ON_FORM_ACTION, function () {
            Notification::info($this->sendServerInfoToSocket());
            $this->redirectNow($this->url());
        });
        if (count($table) === 0) {
            $this->content()->add(Hint::info($this->translate('Please define your first Server Connection')));
        } else {
            $table->renderTo($this);
        }
    }

    protected function mapServerConnectionsToId($connections)
    {
        $connectionsByServer = [];
        foreach ((array) $connections as $id => $connection) {
            if (isset($connectionsByServer[$connection->serverId])) {
                $connectionsByServer[$connection->serverId][$id] = $connection;
            } else {
                $connectionsByServer[$connection->serverId] = [$id => $connection];
            }
        }

        return $connectionsByServer;
    }

    public function monitoringAction()
    {
        $this->tabs(new ConfigTabs($this->db()))->activate('monitoring');
        $this->actions()->add(Link::create(
            $this->translate('Add'),
            'vspheredb/configuration/addmonitoring',
            null,
            [
                'class'            => 'icon-plus',
                'data-base-target' => '_next',
            ]
        ));
        $this->addTitle($this->translate('Monitoring Integration'));
        $table = new MonitoredObjectMappingTable($this->db());
        $table->handleSortPriorityActions($this->getRequest(), $this->getResponse());
        if (count($table)) {
            $wrapper = Html::tag('div', ['class' => 'icinga-module module-director']);
            $wrapper->wrap($table);
            $this->content()->add($wrapper);
            $table->renderTo($this);
        } else {
            $this->content()->add(Hint::warning($this->translate(
                'No integration has been configured'
            )));
        }
    }

    public function addmonitoringAction()
    {
        $this->addSingleTab($this->translate('Create'));
        $this->addTitle($this->translate('New Monitoring Integration'));
        $form = new MonitoringConnectionForm($this->db());
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
    }

    public function monitoringconfigAction()
    {
        $id = $this->params->get('id');
        if ($id) {
            $this->addSingleTab($this->translate('Modify'));

            $db = $this->db()->getDbAdapter();
            $res = $db->fetchRow(
                $db->select()->from('monitoring_connection')->where('id = ?', $id)
            );
        } else {
            $this->addSingleTab($this->translate('Create'));
            $res = null;
        }


        $this->addTitle($this->translate('Monitoring Integration'));
        $form = new MonitoringConnectionForm($this->db());
        $form->on(MonitoringConnectionForm::ON_SUCCESS, function (MonitoringConnectionForm $form) {
            // TODO: created, modified, nothing, %s
            Notification::success($this->translate('Monitoring Integration has been stored'));
            $this->redirectNow($this->url());
        });
        if ($res) {
            if ($res->vcenter_uuid !== null) {
                $res->vcenter = Uuid::fromBytes($res->vcenter_uuid)->toString();
            }
            $form->populate((array) $res);
        }
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
    }
}
