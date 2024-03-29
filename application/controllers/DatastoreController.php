<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\Web\Table\NameValueTable;
use Icinga\Module\Vspheredb\Data\Anonymizer;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\EventHistoryTable;
use Icinga\Module\Vspheredb\Web\Table\VmsOnDatastoreTable;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Icinga\Util\Format;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;

class DatastoreController extends Controller
{
    use SingleObjectMonitoring;

    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function indexAction()
    {
        $ds = $this->addDatastore();
        $lookup = new PathLookup($this->db()->getDbAdapter());
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($ds->get('uuid'), false)) as $parentUuid => $name) {
            $name = Anonymizer::anonymizeString($name);
            $path->add(Link::create(
                $name,
                'vspheredb/datastores',
                ['uuid' => Uuid::fromBytes($parentUuid)->toString()],
                ['data-base-target' => '_main']
            ));
        }

        gc_collect_cycles();
        gc_disable();
        $usage = (new DatastoreUsage($ds))->loadAllVmDisks()->addFreeDatastoreSpace();
        gc_collect_cycles();
        gc_enable();

        $table = new NameValueTable();
        $statusRenderer = new OverallStatusRenderer();
        $table->addNameValuePairs([
            $this->translate('Status') => $statusRenderer($ds->object()->get('overall_status')),
            $this->translate('Path') => $path,
            $this->translate('Capacity') => $this->bytes($ds->get('capacity')),
            $this->translate('Free') => $this->bytes($ds->get('free_space')),
            $this->translate('Used') => $this->bytes(
                $ds->get('capacity') - $ds->get('free_space')
            ),
            $this->translate('Uncommitted') => $this->bytes($ds->get('uncommitted')),
            $this->translate('Sizing') => $this->sizingInfo($ds),
        ]);
        $vms = VmsOnDatastoreTable::create($ds);

        $this->content()->add([$table, $usage, $vms]);
    }

    /**
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    public function eventsAction()
    {
        $ds = $this->addDatastore();
        $table = new EventHistoryTable($this->db());
        $table->filterDatastore($ds)
            ->renderTo($this);
    }

    public function monitoringAction()
    {
        $this->showMonitoringDetails($this->addDatastore());
    }

    /**
     * @return Datastore
     * @throws \Icinga\Exception\MissingParameterException
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function addDatastore()
    {
        $ds = Datastore::loadWithUuid($this->params->getRequired('uuid'), $this->db());
        $ds->object()->set('object_name', Anonymizer::anonymizeString($ds->object()->get('object_name')));
        $this->getRestrictionHelper()->assertAccessToVCenterUuidIsGranted($ds->get('vcenter_uuid'));
        $this->addTitle($ds->object()->get('object_name'));
        $this->handleTabs();

        return $ds;
    }

    protected function handleTabs()
    {
        $params = ['uuid' => $this->params->get('uuid')];
        $this->tabs()->add('index', [
            'label'     => $this->translate('Datastore'),
            'url'       => 'vspheredb/datastore',
            'urlParams' => $params
        ])->add('events', [
            'label'     => $this->translate('Events'),
            'url'       => 'vspheredb/datastore/events',
            'urlParams' => $params
        ])->add('monitoring', [
            'label'     => $this->translate('Monitoring'),
            'url'       => 'vspheredb/datastore/monitoring',
            'urlParams' => $params
        ])->activate($this->getRequest()->getActionName());
    }

    protected function sizingInfo(Datastore $ds)
    {
    }

    protected function bytes($bytes)
    {
        return Format::bytes($bytes, Format::STANDARD_IEC);
    }
}
