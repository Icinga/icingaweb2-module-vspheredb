<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\OverviewTree;
use Icinga\Module\Vspheredb\Web\Table\DatastoreInFolderTable;
use Icinga\Module\Vspheredb\Web\Table\HostsInFolderTable;
use Icinga\Module\Vspheredb\Web\Table\VmsInFolderTable;
use dipl\Html\Html;
use dipl\Html\Link;

class OverviewController extends Controller
{
    public function indexAction()
    {
        $this
            ->addSingleTab($this->translate('vSphere'))
            ->addTitle('vSphere Overview');
        $this->content()->add(
            new OverviewTree($this->db(), $this->params->get('type'))
        );
    }

    public function hostsAction()
    {
        $parent = $this->params->getRequired('id');
        $lookup = new PathLookup($this->db());
        $name = $lookup->getObjectName($parent);
        $ids = $lookup->listFoldersBelongingTo($parent);
        $this
            ->addSingleTab($this->translate('Hosts'))
            ->addTitle($name);
        $this->actions()->add(
            Link::create(
                $this->translate('back'),
                'vspheredb/overview',
                null,
                [
                    'data-base-target' => '_main',
                    'class' => 'icon-left-big'
                ]
            )
        );
        $table = new HostsInFolderTable($this->db());
        $table->filterParentIds([$parent]);
        // $table->filterParentIds($ids);
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($parent)) as $id => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/overview/hosts',
                ['id' => $id]
            ));
        }
        $this->content()->add([
            $path,
            sprintf(': %d Hosts', count($table))
        ]);
        $table->renderTo($this);
    }

    public function vmsAction()
    {
        $parent = $this->params->getRequired('id');
        $lookup = new PathLookup($this->db());
        $name = $lookup->getObjectName($parent);
        $ids = $lookup->listFoldersBelongingTo($parent);
        $this
            ->addSingleTab($this->translate('VMs'))
            ->addTitle($name);
        $this->actions()->add(
            Link::create(
                $this->translate('back'),
                'vspheredb/overview',
                null,
                [
                    'data-base-target' => '_main',
                    'class' => 'icon-left-big'
                ]
            )
        );
        $table = new VmsInFolderTable($this->db());
        // $table->filterParentIds($ids);
        $table->filterParentIds([$parent]);
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($parent)) as $id => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/overview/vms',
                ['id' => $id]
            ));
        }
        $this->content()->add([
            $path,
            sprintf(': %d Virtual Machines', count($table))
        ]);
        $table->renderTo($this);
    }

    public function datastoresAction()
    {
        $parent = $this->params->getRequired('id');
        $lookup = new PathLookup($this->db());
        $name = $lookup->getObjectName($parent);
        $ids = $lookup->listFoldersBelongingTo($parent);
        $this
            ->addSingleTab($this->translate('Datatores'))
            ->addTitle($name);
        $this->actions()->add(
            Link::create(
                $this->translate('back'),
                'vspheredb/overview',
                null,
                [
                    'data-base-target' => '_main',
                    'class' => 'icon-left-big'
                ]
            )
        );
        $table = new DatastoreInFolderTable($this->db());
        $table->filterParentIds([$parent]);
        // $table->filterParentIds($ids);
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($parent)) as $id => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/overview/datastores',
                ['id' => $id]
            ));
        }
        $this->content()->add([
            $path,
            sprintf(': %d Datastores', count($table))
        ]);
        $table->renderTo($this);
    }
}
