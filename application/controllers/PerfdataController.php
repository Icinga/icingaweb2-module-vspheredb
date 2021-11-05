<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Web\Widget\Hint;
use gipfl\ZfDbStore\ZfDbStore;
use Icinga\Module\Vspheredb\Storable\PerfdataConsumer;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Form\FilterVCenterForm;
use Icinga\Module\Vspheredb\Web\Form\PerfdataConsumerForm;
use Icinga\Module\Vspheredb\Web\Table\PerfDataConsumerTable;
use Icinga\Module\Vspheredb\Web\Table\PerformanceCounterTable;
use Icinga\Module\Vspheredb\Web\Tabs\ConfigTabs;
use Icinga\Module\Vspheredb\Web\Tabs\VCenterTabs;
use Icinga\Module\Vspheredb\Web\Widget\AdditionalTableActions;
use Icinga\Web\Notification;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;

class PerfdataController extends Controller
{
    use AsyncControllerHelper;

    public function countersAction()
    {
        $vCenter = $this->requireVCenter();
        $this->tabs(new VCenterTabs($vCenter))->activate('perfcounters');
        $this->addTitle($this->translate('Available Performance Counters'));
        $form = new FilterVCenterForm($this->db());
        $form->handleRequest($this->getServerRequest());
        $this->content()->add(Html::tag('div', ['class' => 'icinga-module module-director'], $form));
        $uuid = $form->getHexUuid();
        if ($uuid === null) {
            return;
        }
        $table = (new PerformanceCounterTable($this->db(), $this->url(), $vCenter));
        (new AdditionalTableActions($table, $this->Auth(), $this->url()))
            ->appendTo($this->actions());
        $table->renderTo($this);
    }

    public function consumersAction()
    {
        $this->setAutorefreshInterval(10);
        $this->tabs(new ConfigTabs())->activate('perfdata');
        $this->addTitle($this->translate('Performance Data Consumers'));
        $this->actions()->add(Link::create($this->translate('Add'), 'vspheredb/perfdata/consumer', null, [
            'data-base-target' => '_next',
            'class'            => 'icon-plus',
        ]));
        $table = new PerfDataConsumerTable($this->db()->getDbAdapter());
        if (count($table) === 0) {
            $this->content()->add(Hint::info($this->translate('Please create your first Performance Data Consumer')));
            return;
        }
        $table->renderTo($this);
    }

    public function consumerAction()
    {
        $store = new ZfDbStore($this->db()->getDbAdapter());
        $form = new PerfdataConsumerForm($this->loop(), $this->remoteClient(), $store);
        $form->on($form::ON_DELETE, function () {
            Notification::success($this->translate('Performance Data Consumer has been removed'));
            $this->redirectNow('vspheredb/perfdata/consumers');
        });
        $form->on(PerfdataConsumerForm::ON_SUCCESS, function (PerfdataConsumerForm $form) {
            if ($form->wasNew()) {
                Notification::success($this->translate('Performance Data Consumer has been created'));
            } else {
                Notification::success($this->translate('Performance Data Consumer has been updated'));
            }
            $this->redirectNow(Url::fromPath('vspheredb/perfdata/consumer', [
                'uuid' => Uuid::fromBytes($form->getObject()->get('uuid'))->toString()
            ]));
        });
        $uuid = $this->params->get('uuid');
        if ($uuid === null) {
            $this->addSingleTab($this->translate('Add Consumer'));
            $this->addTitle($this->translate('Define a new Performance Data Consumer'));
        } else {
            $this->addSingleTab($this->translate('Consumer'));
            $uuid = Uuid::fromString($uuid);
            $consumer = $store->load($uuid->getBytes(), PerfdataConsumer::class);
            $this->addTitle($consumer->get('name'));
            $form->setObject($consumer);
        }
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
    }
}
