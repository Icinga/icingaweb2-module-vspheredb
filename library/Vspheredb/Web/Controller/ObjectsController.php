<?php

namespace Icinga\Module\Vspheredb\Web\Controller;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\ProvidedHook\Director\ImportSource;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Form\FilterVCenterForm;
use Icinga\Module\Vspheredb\Web\Table\TableWithParentFilter;
use Icinga\Module\Vspheredb\Web\Table\TableWithVCenterFilter;
use ipl\Html\Html;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Objects\ObjectsTable;
use Ramsey\Uuid\Uuid;

class ObjectsController extends Controller
{
    use RestApi;

    protected $otherTabActions = [];

    /** @var PathLookup */
    protected $pathLookup;
    protected $vCenterFilterForm;

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

    protected function addTreeViewToggle()
    {
        if ($this->params->get('render') === 'tree') {
            $this->actions()->add(
                Link::create(
                    $this->translate('Table'),
                    $this->url()->without('render'),
                    null,
                    ['class' => 'icon-sitemap']
                )
            );
        } else {
            $this->actions()->add(
                Link::create(
                    $this->translate('Tree'),
                    $this->url()->with('render', 'tree'),
                    null,
                    ['class' => 'icon-sitemap']
                )
            );
        }
    }

    protected function eventuallyFilterByParent(TableWithParentFilter $table, $url, $defaultTitle = null)
    {
        $parent = $this->params->get('parent');
        if ($parent === null) {
            $parent = $this->params->get('uuid');
        }
        if ($parent !== null) {
            $parent = Uuid::fromString($parent)->getBytes();
        }

        if ($parent) {
            $lookup = $this->pathLookup();
            $name = $lookup->getObjectName($parent);
            if ($name) {
                $this->addTitle($name);
            } else {
                $this->addTitle($defaultTitle);
            }
            if ($this->params->get('showDescendants')) {
                $uuids = $lookup->listFoldersBelongingTo($parent);
                $table->filterParentUuids($uuids);
            } else {
                $table->filterParentUuids([$parent]);
            }
            $this->addPathTo($parent, $url);
        } elseif ($defaultTitle !== null) {
            $this->addTitle($defaultTitle);
        }
    }

    protected function eventuallyFilterByVCenter(TableWithVCenterFilter $table)
    {
        $this->getRestrictionHelper()->restrictTable($table);
        $this->getVCenterFilterForm();

        if ($uuid = $this->params->get('vcenter')) {
            $table->filterVCenter(VCenter::loadWithUuid($uuid, $this->db()));
        }
    }

    protected function getVCenterFilterForm(): FilterVCenterForm
    {
        if ($this->vCenterFilterForm === null) {
            $form = new FilterVCenterForm($this->db(), $this->Auth());
            $form->allowAllVCenters();
            $form->getAttributes()->add('style', 'float: right');
            $form->handleRequest($this->getServerRequest());
            $this->vCenterFilterForm = $form;
        }

        return $this->vCenterFilterForm;
    }

    protected function showTable(ObjectsTable $table, $url, $defaultTitle = null)
    {
        $this->eventuallyFilterByParent($table, $url, $defaultTitle);
        $this->eventuallyFilterByVCenter($table);
        $this->renderTableWithCount($table, $defaultTitle);
        $this->controls()->prepend($this->getVCenterFilterForm());

        return $this;
    }

    protected function renderTableWithCount(ObjectsTable $table, $title = null)
    {
        $total = count($table);
        $table->renderTo($this);
        if ($title === null) {
            return;
        }
        $found = count($table);
        if ($total === $found) {
            $this->content()->prepend(sprintf('%d %s', $total, $title));
        } else {
            $this->content()->prepend(sprintf('%d out of %d %s', $found, $total, $title));
        }
    }

    protected function downloadTable(ObjectsTable $table, string $title)
    {
        $this->eventuallyFilterByParent($table, Url::fromPath(''), $title);
        $this->eventuallyFilterByVCenter($table);
        $query = $table->getQuery();
        $rows = $this->db()->getDbAdapter()->fetchAll($query);
        foreach ($rows as $row) {
            ImportSource::convertDbRowToJsonData($row);
        }
        $this->downloadJson($this->getResponse(), $rows, "$title.json");
    }

    protected function sendExport($type)
    {
        $import = new ImportSource();
        $import->setSettings([
            'object_type' => $type
        ]);
        $this->eventuallyFilterByVCenter($import);
        $this->eventuallyFilterByParent($import, $this->url());
        $this->downloadJson($this->getResponse(), $import->fetchData(), $type . 's.json');
    }

    protected function addPathTo($parent, $url)
    {
        $lookup = $this->pathLookup();
        $path = Html::tag('span', ['class' => 'dc-path']);
        $first = true;
        foreach ($lookup->getObjectNames($lookup->listPathTo($parent)) as $uuid => $name) {
            if ($first) {
                $first = false;
            } else {
                $path->add(' > ');
            }
            $path->add(Link::create($name, $url, [
                'parent'          => Util::niceUuid($uuid),
                'showDescendants' => true,
            ]));
        }

        $this->content()->add($path);
    }

    protected function handleTabs()
    {
        $action = $this->getRequest()->getControllerName();
        if (isset($this->otherTabActions[$action])) {
            $action = $this->otherTabActions[$action];
        }
        $urlParams = $this->getParentParamsToPreserve();

        $this->tabs()->add('vms', [
            'label'     => $this->translate('Virtual Machine'),
            'url'       => 'vspheredb/vms',
            'urlParams' => $urlParams,
        ])->add('hosts', [
            'label'     => $this->translate('Hosts'),
            'url'       => 'vspheredb/hosts',
            'urlParams' => $urlParams,
        ])->add('datastores', [
            'label'     => $this->translate('Datastores'),
            'url'       => 'vspheredb/datastores',
            'urlParams' => $urlParams,
        ])
        // ->add('switches', [
        //     'label'     => $this->translate('Switches'),
        //     'url'       => 'vspheredb/switches',
        // ])
        ->activate($action);
    }

    protected function getParentParamsToPreserve(): array
    {
        $urlParams = [];
        foreach (['showDescendants', 'uuid', 'parent', 'vcenter'] as $param) {
            if (null !== ($value = $this->url()->getParam($param))) {
                $urlParams[$param] = $value;
            }
        }

        return $urlParams;
    }

    protected function pathLookup(): PathLookup
    {
        if ($this->pathLookup === null) {
            $this->pathLookup = new PathLookup($this->db()->getDbAdapter());
        }

        return $this->pathLookup;
    }
}
