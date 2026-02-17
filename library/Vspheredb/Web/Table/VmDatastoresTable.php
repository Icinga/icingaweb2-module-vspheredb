<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\ZfDb\Select;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use Icinga\Util\Format;
use ipl\Html\HtmlElement;
use Zend_Db_Select;

class VmDatastoresTable extends ZfQueryBasedTable
{
    protected $searchColumns = ['object_name'];

    protected $parentIds;

    /** @var VirtualMachine */
    protected VirtualMachine $vm;

    /** @var ?string */
    protected ?string $uuid = null;

    /** @var OverallStatusRenderer */
    protected OverallStatusRenderer $renderStatus;

    public function __construct(VirtualMachine $vm)
    {
        $this->setVm($vm);
        parent::__construct($vm->getConnection());
        $title = new SubTitle($this->translate('DataStore Usage'), 'database');
        $this->prepend($title);
        $this->renderStatus = new OverallStatusRenderer();
    }

    protected function setVm(VirtualMachine $vm): static
    {
        $this->vm = $vm;
        $this->uuid = $vm->get('uuid');

        return $this;
    }

    public function getColumnsToBeRendered(): array
    {
        return [
            $this->translate('Status'),
            $this->translate('Datastore'),
            $this->translate('Size'),
            $this->translate('Usage'),
            $this->translate('On Datastore'),
        ];
    }

    public function renderRow($row): HtmlElement
    {
        $size = $row->committed + $row->uncommitted;
        $caption = Link::create(
            $row->object_name,
            'vspheredb/datastore',
            Util::uuidParams($row->uuid),
            ['title' => sprintf($this->translate('Datastore: %s'), $row->object_name)]
        );

        /** @var Db $connection */
        $connection = $this->connection();
        $datastore = Datastore::load($row->uuid, $connection);
        $usage = (new DatastoreUsage($datastore))
            ->setBaseUrl('vspheredb/datastore')
            ->setCapacity($size)
            ->addDiskFromDbRow($row);
        $usage->getAttributes()->add('class', 'compact');
        $dsUsage = (new DatastoreUsage($datastore))
            ->setBaseUrl('vspheredb/datastore')
            ->addDiskFromDbRow($row);
        $dsUsage->getAttributes()->add('class', 'compact');

        $renderStatus = $this->renderStatus;
        $tr = $this::tr([
            $this::td($renderStatus($row->overall_status)),
            $this::td($caption, ['class' => 'vm-datastore-caption']),
            $this::td(Format::bytes($size), ['class' => 'vm-datastore-size']),
            $this::td($usage, ['class' => 'vm-datastore-usage']),
            $this::td($dsUsage, ['class' => 'vm-datastore-on-datastore']),
        ]);

        return $tr;
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            [
                'uuid'           => 'o.uuid',
                'overall_status' => 'o.overall_status',
                'object_name'    => 'o.object_name',
                'committed'      => 'vdu.committed',
                'uncommitted'    => 'vdu.uncommitted',
            ]
        )->join(
            ['vdu' => 'vm_datastore_usage'],
            'vdu.datastore_uuid = o.uuid',
            []
        )->where('vdu.vm_uuid = ?', $this->uuid)->order('object_name ASC');

        return $query;
    }
}
