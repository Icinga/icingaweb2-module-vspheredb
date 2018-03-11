<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Html\Icon;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\Web\Widget\DatastoreUsage;
use Icinga\Util\Format;
use dipl\Html\Link;

class DatastoreTable extends ObjectsTable
{
    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createColumn('overall_status', $this->translate('Status'), 'o.overall_status')
                ->setRenderer(function ($row) {
                    return Icon::create('ok', [
                        'title' => $this->getStatusDescription($row->overall_status),
                        'class' => [ 'state', $row->overall_status ]
                    ]);
                })->setDefaultSortDirection('DESC'),
            $this->createColumn('object_name', $this->translate('Name'), [
                'object_name'          => 'o.object_name',
                'uuid'                 => 'o.uuid',
                'capacity'             => 'ds.capacity',
                'free_space'           => 'ds.free_space',
                'uncommitted'          => 'ds.uncommitted',
                'free_space_percent'   => '(ds.free_space / ds.capacity) * 100',
                'uncommitted_percent'  => '(ds.uncommitted / ds.capacity) * 100',
            ])->setRenderer(function ($row) {
                $title = sprintf(
                    // '%d VM(s), %s of %s used, %s uncommitted',
                    '%s of %s used, %s uncommitted',
                    // $row->cnt_vm,
                    $this->formatBytesPercent($row, 'free_space'),
                    Format::bytes($row->capacity, Format::STANDARD_IEC),
                    $this->formatBytesPercent($row, 'uncommitted')
                );

                return Link::create(
                    $row->object_name,
                    'vspheredb/datastore',
                    ['uuid' => bin2hex($row->uuid)],
                    ['title' => $title]
                );
            }),
            $this->createColumn('free_space', $this->translate('Free'), 'ds.free_space')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->free_space, Format::STANDARD_IEC);
                }),
            $this->createColumn('cnt_vms', $this->translate('VMs'), [
                'cnt_vms' => 'COALESCE(vdu.cnt_vms, 0)',
            ])->setDefaultSortDirection('DESC'),
            $this->createColumn('free_space_percent', $this->translate('Free (%)'), [
                'free_space_percent'  => '(ds.free_space / ds.capacity) * 100'
            ])->setRenderer(function ($row) {
                return $this->formatPercent($row->free_space_percent);
            }),
            $this->createColumn('uncommitted', $this->translate('Uncommitted'), 'ds.uncommitted')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->uncommitted, Format::STANDARD_IEC);
                }),
            $this->createColumn('uncommitted_percent', $this->translate('Uncommitted (%)'), [
                'uncommitted_percent'  => '(ds.uncommitted / ds.capacity) * 100'
            ])->setRenderer(function ($row) {
                return $this->formatPercent($row->free_space_percent);
            }),
            $this->createColumn('size', $this->translate('Size'), 'ds.capacity')
                ->setRenderer(function ($row) {
                    return Format::bytes($row->capacity, Format::STANDARD_IEC);
                }),
            $this->createColumn('usage', $this->translate('Usage'), [
                'uuid' => 'uuid'
            ])->setSortExpression('1 - (ds.free_space / ds.capacity) ')
                ->setRenderer(function ($row) {
                    /** @var Db $connection */
                    $connection = $this->connection();
                    $usage = new DatastoreUsage(Datastore::load($row->uuid, $connection));
                    $usage->attributes()->add('class', 'compact');
                    $usage->loadAllVmDisks()->addFreeDatastoreSpace();

                    return $usage;
                }),
        ]);
    }

    public function getDefaultColumnNames()
    {
        return [
            'overall_status',
            'object_name',
            'usage',
        ];
    }

    protected function formatBytesPercent($row, $name)
    {
        $bytes = $row->$name;
        $percent = $row->{"${name}_percent"};
        return sprintf(
            '%s (%s)',
            Format::bytes($bytes, Format::STANDARD_IEC),
            $this->formatPercent($percent)
        );
    }

    protected function formatPercent($value)
    {
        return sprintf('%0.2f%%', $value);
    }

    protected function getStatusDescription($status)
    {
        $descriptions = [
            'gray'   => $this->translate('Gray - status is unknown'),
            'green'  => $this->translate('Green - everything is fine'),
            'yellow' => $this->translate('Yellow - there are warnings'),
            'red'    => $this->translate('Red - there is a problem'),
        ];

        return $descriptions[$status];
    }

    public function sortBy($columns)
    {
        parent::sortBy($columns);

        $this->getQuery()->order('object_name');

        return $this;
    }


    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $this->getRequiredDbColumns()
        )->join(
            ['ds' => 'datastore'],
            'o.uuid = ds.uuid',
            []
        )->group('o.uuid');

        if ($this->hasColumn('cnt_vms')) {
            $vduQuery = $this->db()->select()->from('vm_datastore_usage', [
                'cnt_vms' => 'COUNT(*)',
                'ds_uuid' => 'datastore_uuid',
            ])->group('datastore_uuid');
            $query->joinLeft(
                ['vdu' => $vduQuery],
                'vdu.ds_uuid = o.uuid',
                []
            );
        }

        if ($this->parentUuids) {
            $query->where('o.parent_uuid IN (?)', $this->parentUuids);
        }

        return $query;
    }
}
