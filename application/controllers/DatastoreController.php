<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\VmsOnDatastoreTable;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Util\Format;
use dipl\Html\Html;
use dipl\Html\Link;
use dipl\Web\Widget\NameValueTable;

class DatastoreController extends Controller
{
    public function indexAction()
    {
        $id = $this->params->getRequired('id');
        $ds = Datastore::load($id, $this->db());
        $object = $ds->object();

        $this
            ->addSingleTab($this->translate('Datastore'))
            ->addTitle($object->get('object_name'));

        $lookup = new PathLookup($this->db());
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($id, false)) as $parentId => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/overview/datastores',
                ['id' => $parentId],
                ['data-base-target' => '_main']
            ));
        }

        $usage = (new DatastoreUsage($ds))->loadAllVmDisks()->addFreeDatastoreSpace();
        $table = new NameValueTable();
        $table->addNameValuePairs([
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

    protected function sizingInfo(Datastore $ds)
    {

    }

    protected function bytes($bytes)
    {
        return Format::bytes($bytes, Format::STANDARD_IEC);
    }
}
