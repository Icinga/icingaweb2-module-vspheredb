<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use ipl\Html\DeferredText;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class EventHistoryTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    protected $requiredUuids = [];

    protected $vMotionEvents = [
        'VmFailedMigrateEvent',
        'MigrationEvent',
        'VmBeingMigratedEvent',
        'VmBeingHotMigratedEvent',
        'VmEmigratingEvent',
        'VmMigratedEvent',
    ];

    protected $otherKnownEvents = [
        'VmStartingEvent',
        'VmPoweredOnEvent',
        'VmStoppingEvent',
        'VmPoweredOffEvent',
        'VmResettingEvent',
        'VmBeingCreatedEvent',
        'VmCreatedEvent',
        'VmReconfiguredEvent',
        'VmSuspendedEvent',
        'VmBeingDeployedEvent',
        'VmBeingClonedEvent',
        'VmBeingClonedNoFolderEvent',
        'VmClonedEvent',
        'VmCloneFailedEvent'
    ];

    protected $fetchedUuids;

    /** @var Datastore */
    protected $datastore;

    /** @var HostSystem */
    protected $host;

    /** @var VirtualMachine */
    protected $vm;

    /** @var string */
    protected $eventType;

    /** @var string */
    protected $parent;

    public function renderRow($row)
    {
        $this->renderDayIfNew($row->ts_event_ms / 1000);
        $content = [];

        if (null === $this->vm) {
            $content[] = 'VM: ';
            $content[] = Link::create(
                $this->deferredObjectName($row->vm_uuid),
                'vspheredb/vm/events',
                ['uuid' => bin2hex($row->vm_uuid)]
            );
            $content[] = Html::tag('br');
        }

        if (in_array($row->event_type, $this->vMotionEvents)) {
            $content[] = 'Path: ';
            $content[] = $this->deferredVMotionPath($row);
            if ($row->user_name !== null) {
                $content[] = Html::tag('br');
                $content[] = sprintf('User: %s', $row->user_name);
            }
        } elseif (in_array($row->event_type, $this->otherKnownEvents)) {
            $content[] = $row->full_message;
        }
        $tr = $this::row([
            $content,
            DateFormatter::formatTime($row->ts_event_ms / 1000)
        ]);

        // TODO:
        // 'VmBeingClonedEvent',
        // 'VmBeingClonedNoFolderEvent',
        // 'VmClonedEvent',
        // 'VmCloneFailedEvent'
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
            case 'VmResettingEvent':
                $tr->addAttributes([
                    'class' => 'state poweredOff',
                ]);
                break;
            case 'VmPoweredOffEvent':
                $tr->addAttributes([
                    'class' => 'state poweredOff',
                ]);
                break;
            case 'VmStartingEvent':
                $tr->addAttributes([
                    'class' => 'state starting',
                ]);
                break;
            case 'VmPoweredOnEvent':
                $tr->addAttributes([
                    'class' => 'state poweredOn',
                ]);
                break;
            case 'VmStoppingEvent':
                $tr->addAttributes([
                    'class' => 'state stopping',
                ]);
                break;
            case 'VmSuspendedEvent':
                $tr->addAttributes([
                    'class' => 'event suspended',
                ]);
                break;
            case 'VmReconfiguredEvent':
                $tr->addAttributes([
                    'class' => 'event reconfigured',
                ]);
                break;
            case 'VmBeingCreatedEvent':
            case 'VmBeingDeployedEvent':
                $tr->addAttributes([
                    'class' => 'event being-created',
                ]);
                break;
            case 'VmCreatedEvent':
                $tr->addAttributes([
                    'class' => 'event created',
                ]);
                break;
            default:
                $tr->add($this::td(Html::tag('pre', null, print_r($row, 1))));
        }

        $tr->addAttributes([
            'title' => sprintf('%s (%s)', $row->full_message, $row->event_type)
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

    public function filterEventType($type)
    {
        if (is_array($type)) {
            $this->eventType = $type;
        } elseif (strlen($type)) {
            $this->eventType = $type;
        }

        return $this;
    }

    public function filterParent($uuid)
    {
        if (strlen($uuid)) {
            $this->parent = $uuid;
        }

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

    /**
     * @return \Zend_Db_Select
     */
    protected function prepareQuery()
    {
        $query = $this->db()->select()->from([
            'vh' => 'vm_event_history'
        ], [
            'vh.ts_event_ms',
            'vh.event_type',
            'vh.vm_uuid',
            'vh.host_uuid',
            'vh.user_name',
            'vh.datastore_uuid',
            'vh.destination_host_uuid',
            'vh.destination_datastore_uuid',
            'vh.full_message',
            'vh.fault_reason',
        ])->order('ts_event_ms DESC');

        if (is_string($this->eventType) && strlen($this->eventType)) {
            $query->where('event_type = ?', $this->eventType);
        } elseif (is_array($this->eventType) && ! empty($this->eventType)) {
            $query->where('event_type IN (?)', $this->eventType);
        }

        if ($this->parent !== null) {
            $query->join(
                ['h' => 'object'],
                $this->db()->quoteInto(
                    'h.uuid = vh.host_uuid AND h.parent_uuid = UNHEX(?)',
                    $this->parent
                ),
                []
            );
        }

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

    protected function deferredObjectName($uuid)
    {
        $this->requiredUuids[$uuid] = $uuid;

        $content = new DeferredText(function () use ($uuid) {
            return $this->getUuidName($uuid);
        });

        return $content->setEscaped();
    }

    /**
     * @param $row
     * @return HtmlDocument
     */
    protected function showMotionPath($row)
    {
        $html = new HtmlDocument();
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

    /**
     * @param $row
     * @return \ipl\Html\FormattedString
     */
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
                Icon::create('right-big')
            );
        } else {
            return Html::sprintf(
                '%s %s %s',
                Link::create(
                    $this->getUuidName($row->host_uuid),
                    'vspheredb/host',
                    ['uuid' => bin2hex($row->host_uuid)]
                ),
                Icon::create('right-big'),
                Link::create(
                    $this->getUuidName($row->destination_host_uuid),
                    'vspheredb/host',
                    ['uuid' => bin2hex($row->destination_host_uuid)]
                )
            );
        }
    }

    /**
     * @param $row
     * @return \ipl\Html\FormattedString
     */
    protected function showDatastoreToDatastoreMigration($row)
    {
        return Html::sprintf(
            '%s %s %s',
            Link::create(
                $this->getUuidName($row->datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => bin2hex($row->datastore_uuid)]
            ),
            Icon::create('right-big'),
            Link::create(
                $this->getUuidName($row->destination_datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => bin2hex($row->destination_datastore_uuid)]
            )
        );
    }

    /**
     * @param $row
     * @return \ipl\Html\FormattedString
     */
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

    /**
     * @param $row
     * @return \ipl\Html\FormattedString
     */
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

    /**
     * @param $row
     * @return \ipl\Html\FormattedString
     */
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

    /**
     * @param $row
     * @return \ipl\Html\FormattedString
     */
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
