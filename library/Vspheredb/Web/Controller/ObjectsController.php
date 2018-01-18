<?php

namespace Icinga\Module\Vspheredb\Web\Controller;

use dipl\Html\Html;
use dipl\Html\Link;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Objects\ObjectsTable;

class ObjectsController extends Controller
{
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

    protected function showTable(ObjectsTable $table, $url, $defaultTitle)
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

        $this->renderTableWithCount($table, $defaultTitle);

        return $this;
    }

    protected function renderTableWithCount(ObjectsTable $table, $title)
    {
        $total = count($table);
        $table->renderTo($this);
        $found = count($table);
        if ($total === $found) {
            $this->content()->prepend(sprintf('%d %s', $total, $title));
        } else {
            $this->content()->prepend(sprintf('%d out of %d %s', $found, $total, $title));
        }
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
}
