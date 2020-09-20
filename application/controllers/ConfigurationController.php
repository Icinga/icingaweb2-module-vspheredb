<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\Web\Widget\Hint;
use Icinga\Module\Vspheredb\Web\Form\ChooseDbResourceForm;
use Icinga\Module\Vspheredb\Web\Form\MonitoringConnectionForm;
use Icinga\Module\Vspheredb\Web\Table\MonitoredObjectMappingTable;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Widget\Config\ProposeMigrations;
use ipl\Html\Html;

class ConfigurationController extends Controller
{
    public function indexAction()
    {
        $this->addTitle($this->translate('Main Configuration'));

        if (! $this->hasPermission('vspheredb/admin')) {
            $this->addSingleTab($this->translate('Configuration'));
            $this->content()->add(Hint::error(
                $this->translate('"vspheredb/admin" permissions are required to continue')
            ));

            return;
        }
        $form = new ChooseDbResourceForm();
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
        if ($form->hasMessages()) {
            $this->addSingleTab($this->translate('Configuration'));

            return;
        }

        if ($this->Config()->get('db', 'resource')) {
            $db = $this->db();
            $this->tabs(new MainTabs($this->Auth(), $db))
                ->activate('configuration');

            if ($db === null) {
                return;
            }

            $migrations = new ProposeMigrations($db, $this->Auth(), $this->getServerRequest());
            if ($migrations->hasAppliedMigrations()) {
                $this->redirectNow($this->url());
            }
            $this->content()->add($migrations);
        } else {
            $this->tabs(new MainTabs($this->Auth()))->activate('configuration');
        }
    }

    public function monitoringAction()
    {
        $this->tabs(new MainTabs($this->Auth()))->activate('monitoring');
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
        if ($res) {
            $res->vcenter = \bin2hex($res->vcenter_uuid);
            $form->populate((array) $res);
        }
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
    }
}
