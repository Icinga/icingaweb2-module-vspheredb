<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Html\DeferredText;
use dipl\Html\Html;
use dipl\Html\Icon;
use dipl\Html\Link;
use dipl\Web\Table\ZfQueryBasedTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;

class VMotionHistoryTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    protected $requiredUuids = [];

    protected $fetchedUuids;

    /** @var Datastore */
    protected $datastore;

    /** @var HostSystem */
    protected $host;

    /** @var VirtualMachine */
    protected $vm;

    public function renderRow($row)
    {
        $this->renderDayIfNew($row->ts_event_ms / 1000);
        $cols = [
            date('H:i:s', $row->ts_event_ms / 1000),
        ];

        if (null === $this->vm) {
            $cols[] = Link::create(
                $row->object_name,
                'vspheredb/vm/vmotions',
                ['uuid' => bin2hex($row->vm_uuid)]
            );
        }

        $cols[] = $this->deferredVMotionPath($row);
        $tr = $this::row($cols);

        switch ($row->event_type) {
            case 'VmFailedMigrateEvent':
                $tr->addAttributes([
                    'class' => 'state migration-failed',
                ]);
                break;
            case 'DrsVmMigratedEvent':
            case 'VmMigratedEvent':
                $tr->addAttributes([
                    'class' => 'state migrated',
                ]);
                break;
            case 'VmBeingMigratedEvent':
                $tr->addAttributes([
                    'class' => 'state migrating',
                ]);
                break;
            case 'VmBeingHotMigratedEvent':
                $tr->addAttributes([
                    'class' => 'state migrating',
                ]);
                break;
            case 'VmEmigratingEvent':
                $tr->addAttributes([
                    'class' => 'state emigrating',
                ]);
                break;
        }

        $tr->addAttributes([
            'title' => sprintf('%s (%s)', $row->message, $row->event_type)
        ]);

        return $tr;
    }

    public function filterVm(VirtualMachine $vm)
    {
        $this->vm = $vm;

        return $this;
    }

    public function filterHost(HostSystem $host)
    {
        $this->host = $host;

        return $this;
    }

    public function filterDatastore(Datastore $datastore)
    {
        $this->datastore = $datastore;

        return $this;
    }

    protected function getUuidName($uuid)
    {
        if ($uuid === null) {
            return '[NULL]';
        }

        if ($this->fetchedUuids === null) {
            $this->fetchUuidNames();
        }

        if (array_key_exists($uuid, $this->fetchedUuids)) {
            return $this->fetchedUuids[$uuid];
        } else {
            return '[UNKNOWN]';
        }
    }

    protected function fetchUuidNames()
    {
        $db = $this->db();
        if (empty($this->requiredUuids)) {
            $this->fetchedUuids = [];

            return;
        }

        $this->fetchedUuids = $db->fetchPairs(
            $db->select()
                ->from('object', ['uuid', 'object_name'])
                ->where('uuid IN (?)', array_values($this->requiredUuids))
        );
    }

    protected function timeSince($ms)
    {
        return DateFormatter::timeAgo($ms);
    }

    protected function prepareQuery()
    {
        $query = $this->db()->select()->from([
            'vh' => 'vmotion_history'
        ], [
            'o.object_name',
            'vh.ts_event_ms',
            'vh.event_type',
            'vh.vm_uuid',
            'vh.host_uuid',
            'vh.datastore_uuid',
            'vh.destination_host_uuid',
            'vh.destination_datastore_uuid',
            'vh.message',
            'vh.fault_reason',
        ])->join(
            ['o' => 'object'],
            'o.uuid = vh.vm_uuid',
            []
        )->order('ts_event_ms DESC');

        if ($this->datastore) {
            $query->where('datastore_uuid = ?', $this->datastore->get('uuid'))
                ->orWhere('destination_datastore_uuid = ?', $this->datastore->get('uuid'));
        }

        if ($this->host) {
            $query->where('host_uuid = ?', $this->host->get('uuid'))
                ->orWhere('destination_host_uuid = ?', $this->host->get('uuid'));
        }

        if ($this->vm) {
            $query->where('vm_uuid = ?', $this->vm->get('uuid'));
        }

        // $query->where('event_type = ?', 'VmFailedMigrateEvent');
        return $query;
    }

    protected function deferredVMotionPath($row)
    {
        $properties = [
            'host_uuid',
            'destination_host_uuid',
            'datastore_uuid',
            'destination_datastore_uuid',
        ];
        foreach ($properties as $property) {
            $this->requiredUuids[$row->$property] = $row->$property;
        }

        $content = new DeferredText(function () use ($row) {
            return $this->showMotionPath($row);
        });

        return $content->setEscaped();
    }

    protected function showMotionPath($row)
    {
        $html = new Html();
        if ($row->host_uuid !== $row->destination_host_uuid) {
            if ($this->host) {
                if ($row->host_uuid === $this->host->get('uuid')) {
                    $html->add($this->showFromHostMigration($row));
                }

                if ($row->destination_host_uuid === $this->host->get('uuid')) {
                    $html->add($this->showToHostMigration($row));
                }
            } else {
                $html->add($this->showHostToHostMigration($row));
            }
        }

        if ($row->datastore_uuid !== $row->destination_datastore_uuid) {
            if ($this->datastore) {
                if ($row->datastore_uuid === $this->datastore->get('uuid')) {
                    $html->add($this->showFromDatastoreMigration($row));
                }

                if ($row->destination_datastore_uuid === $this->datastore->get('uuid')) {
                    $html->add($this->showToDatastoreMigration($row));
                }
            } else {
                $html->add($this->showDatastoreToDatastoreMigration($row));
            }
        }

        return $html;
    }

    protected function showHostToHostMigration($row)
    {
        if ($row->event_type === 'VmEmigratingEvent') {
            return Html::sprintf(
                '%s %s ?',
                Link::create(
                    $this->getUuidName($row->host_uuid),
                    'vspheredb/host',
                    ['uuid' => bin2hex($row->host_uuid)]
                ),
                Icon::create('flapping')
            );
        } else {
            return Html::sprintf(
                '%s %s %s',
                Link::create(
                    $this->getUuidName($row->host_uuid),
                    'vspheredb/host',
                    ['uuid' => bin2hex($row->host_uuid)]
                ),
                Icon::create('flapping'),
                Link::create(
                    $this->getUuidName($row->destination_host_uuid),
                    'vspheredb/host',
                    ['uuid' => bin2hex($row->destination_host_uuid)]
                )
            );
        }
    }

    protected function showDatastoreToDatastoreMigration($row)
    {
        return Html::sprintf(
            '%s %s %s',
            Link::create(
                $this->getUuidName($row->datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => bin2hex($row->datastore_uuid)]
            ),
            Icon::create('flapping'),
            Link::create(
                $this->getUuidName($row->destination_datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => bin2hex($row->destination_datastore_uuid)]
            )
        );
    }

    protected function showToDatastoreMigration($row)
    {
        return Html::sprintf(
            '%s %s',
            Icon::create('endtime'),
            Link::create(
                $this->getUuidName($row->datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => bin2hex($row->datastore_uuid)]
            )
        );
    }

    protected function showFromDatastoreMigration($row)
    {
        return Html::sprintf(
            '%s %s',
            Icon::create('starttime'),
            Link::create(
                $this->getUuidName($row->destination_datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => bin2hex($row->destination_datastore_uuid)]
            )
        );
    }

    protected function showToHostMigration($row)
    {
        return Html::sprintf(
            '%s %s',
            Icon::create('endtime'),
            Link::create(
                $this->getUuidName($row->host_uuid),
                'vspheredb/host',
                ['uuid' => bin2hex($row->host_uuid)]
            )
        );
    }

    protected function showFromHostMigration($row)
    {
        return Html::sprintf(
            '%s %s',
            Icon::create('starttime'),
            Link::create(
                $this->getUuidName($row->destination_host_uuid),
                'vspheredb/host',
                ['uuid' => bin2hex($row->destination_host_uuid)]
            )
        );
    }
}
