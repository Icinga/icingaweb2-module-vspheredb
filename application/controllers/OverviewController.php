<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\OverviewTree;
use Icinga\Module\Vspheredb\Web\Table\Objects\DatastoreTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\HostsTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\ObjectsTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsTable;
use Icinga\Module\Vspheredb\Web\Table\Objects\VmsInFolderTable;
use dipl\Html\Html;
use dipl\Html\Link;

class OverviewController extends Controller
{
    public function indexAction()
    {
        $type = $this->params->getRequired('type');
        $this->activateTab($type)
             ->addTitle('vSphere Overview')
             ->content()->add(new OverviewTree($this->db(), $type));
    }

    public function hostsAction()
    {
        $this->addSingleTab($this->translate('Hosts'));
        $this->linkBackToOverview('host');

        $table = $this->eventuallyFilterByParent(
            new HostsTable($this->db()),
            'vspheredb/overview/hosts',
            $this->translate('Hosts')
        );
        $total = count($table);
        $table->renderTo($this);
        $found = count($table);
        if ($total === $found) {
            $this->content()->prepend(sprintf('%d Hosts in ', $total));
        } else {
            $this->content()->prepend(sprintf('%d out of %d Hosts in ', $found, $total));
        }
    }

    public function vmsAction()
    {
        $this->addSingleTab($this->translate('VMs'));
        $this->linkBackToOverview('vm');
        $table = $this->eventuallyFilterByParent(
            new VmsInFolderTable($this->db()),
            'vspheredb/overview/vms',
            $this->translate('Virtual Machines')
        );
        $this->content()->add(sprintf(': %d Virtual Machines', count($table)));
        $table->renderTo($this);
    }

    public function allvmsAction()
    {
        $this->activateTab('allvms')
            ->addTitle('Virtual Machines');

        $table = new VmsTable($this->db());
        $total = count($table);
        $table->renderTo($this);
        $found = count($table);

        if ($total === $found) {
            $this->content()->prepend(sprintf('%d Virtual Machines', $total));
        } else {
            $this->content()->prepend(sprintf('%d out of %d Virtual Machines', $found, $total));
        }
    }

    public function datastoresAction()
    {
        $this->addSingleTab($this->translate('Datatores'));
        $this->linkBackToOverview('datastore');
        $table = $this->eventuallyFilterByParent(
            new DatastoreTable($this->db()),
            'vspheredb/overview/datastores',
            $this->translate('Datastores')
        );
        $this->content()->add(sprintf(': %d Datastores', count($table)));
        $table->renderTo($this);
    }

    protected function linkBackToOverview($type)
    {
        $this->actions()->add(
            Link::create(
                $this->translate('back'),
                'vspheredb/overview',
                ['type' => $type],
                [
                    'data-base-target' => '_main',
                    'class' => 'icon-left-big'
                ]
            )
        );

        return $this;
    }

    protected function eventuallyFilterByParent(ObjectsTable $table, $url, $defaultTitle)
    {
        $parent = $this->params->get('id');

        if ($parent) {
            $lookup = $this->pathLookup();
            $name = $lookup->getObjectName($parent);
            $ids = $lookup->listFoldersBelongingTo($parent);
            $this->addTitle($name);
            if ($this->params->get('showDescendants')) {
                $table->filterParentIds($ids);
            } else {
                $table->filterParentIds([$parent]);
            }
            $this->addPathTo($parent, $url);
        } else {
            $this->addTitle($defaultTitle);
        }

        return $table;
    }

    protected function addPathTo($parent, $url)
    {
        $lookup = $this->pathLookup();
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($parent)) as $id => $name) {
            $path->add(Link::create(
                $name,
                $url,
                [
                    'id'              => $id,
                    'showDescendants' => true,
                ]
            ));
        }

        $this->content()->add($path);
    }

    protected function activateTab($name)
    {
        $this->controls()->getTabs()->add('datastore', [
            'label' => $this->translate('Datastores'),
            'url'   => 'vspheredb/overview?type=datastore'
        ])->add('host', [
            'label' => $this->translate('Hosts'),
            'url'   => 'vspheredb/overview?type=host'
        ])->add('vm', [
            'label' => $this->translate('VMs'),
            'url'   => 'vspheredb/overview?type=vm'
        ])->add('allvms', [
            'label' => $this->translate('All VMs'),
            'url'   => 'vspheredb/overview/allvms'
        ])->activate($name);

        return $this;
    }
}
