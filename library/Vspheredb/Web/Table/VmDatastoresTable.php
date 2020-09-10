<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Module\Vspheredb\Web\Widget\OverallStatusRenderer;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use Icinga\Util\Format;

class VmDatastoresTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'object_name',
    ];

    protected $parentIds;

    /** @var VirtualMachine */
    protected $vm;

    /** @var string */
    protected $uuid;

    /** @var OverallStatusRenderer */
    protected $renderStatus;

    public function __construct(VirtualMachine $vm)
    {
        $this->setVm($vm);
        parent::__construct($vm->getConnection());
        $title = new SubTitle($this->translate('DataStore Usage'), 'database');
        $this->prepend($title);
        $this->renderStatus = new OverallStatusRenderer();
    }

    protected function setVm(VirtualMachine $vm)
    {
        $this->vm = $vm;
        $this->uuid = $vm->get('uuid');

        return $this;
    }

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Status'),
            $this->translate('Datastore'),
            $this->translate('Size'),
            $this->translate('Usage'),
            $this->translate('On Datastore'),
        ];
    }

    public function renderRow($row)
    {
        $size = $row->committed + $row->uncommitted;
        $caption = Link::create(
            $row->object_name,
            'vspheredb/datastore',
            ['uuid' => bin2hex($row->uuid)],
            ['title' => sprintf(
                $this->translate('Datastore: %s'),
                $row->object_name
            )]
        );

        /** @var Db $connection */
        $connection = $this->connection();
        $datastore = Datastore::load($row->uuid, $connection);
        $usage = new DatastoreUsage($datastore);
        $usage->setBaseUrl('vspheredb/datastore');
        $usage->setCapacity($size);
        $usage->getAttributes()->add('class', 'compact');
        $usage->addDiskFromDbRow($row);
        $dsUsage = new DatastoreUsage($datastore);
        $dsUsage->setBaseUrl('vspheredb/datastore');
        $dsUsage->getAttributes()->add('class', 'compact');
        $dsUsage->addDiskFromDbRow($row);

        $renderStatus = $this->renderStatus;
        $tr = $this::tr([
            $this::td($renderStatus($row->overall_status)),
            // TODO: move to CSS
            $this::td($caption, ['style' => 'overflow: hidden; display: inline-block; height: 2em; min-width: 8em;']),
            $this::td(Format::bytes($size, Format::STANDARD_IEC), ['style' => 'white-space: pre;']),
            $this::td($usage, ['style' => 'width: 25%;']),
            $this::td($dsUsage, ['style' => 'width: 25%;'])
        ]);

        return $tr;
    }

    public function prepareQuery()
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
