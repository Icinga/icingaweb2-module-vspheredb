<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Date\DateFormatter;

class VmsSnapshotsTable extends ObjectsTable
{
    protected $baseUrl = 'vspheredb/vm';

    protected $searchColumns = [
        'object_name',
        'guest_host_name',
    ];

    public function filterHost($uuid)
    {
        $this->getQuery()->where('vc.runtime_host_uuid = ?', $uuid);

        return $this;
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createColumn('guest_name', $this->translate('Guest hostname'), [
                'object_name'     => 'o.object_name',
                'uuid'            => 'vm.uuid',
                'guest_host_name' => 'vm.guest_host_name',
            ])->setRenderer(function ($row) {
                if ($row->guest_host_name === null || $row->guest_host_name === $row->object_name) {
                    $name = $row->object_name;
                } else {
                    $name = sprintf('%s (%s)', $row->object_name, $row->guest_host_name);
                }

                return Link::create(
                    $name,
                    $this->baseUrl,
                    ['uuid' => bin2hex($row->uuid)]
                );
            }),
            $this->createColumn('cnt', $this->translate('Snapshots'), 'COUNT(*)'),
            $this->createColumn('ts_oldest', $this->translate('Oldest'), 'MIN(vms.ts_create)')
                ->setRenderer(function ($row) {
                    return DateFormatter::formatDate($row->ts_oldest / 1000);
                }),
            $this->createColumn('ts_newest', $this->translate('Newest'), 'MAX(vms.ts_create)')
                ->setRenderer(function ($row) {
                    return DateFormatter::formatDate($row->ts_newest / 1000);
                })
        ]);
    }

    public function prepareQuery()
    {
        $columns = $this->getRequiredDbColumns();
        $query = $this->db()->select()->from(
            ['o' => 'object'],
            $columns
        )->join(
            ['vm' => 'virtual_machine'],
            'o.uuid = vm.uuid',
            []
        )->join(
            ['vms' => 'vm_snapshot'],
            'vms.vm_uuid = vm.uuid',
            []
        )->group('vm.uuid');

        return $query;
    }

    public function XXgetDefaultColumnNames()
    {
        return [
            'object_name',
            'disk_path',
            'free_space',
            'capacity',
        ];
    }
}
