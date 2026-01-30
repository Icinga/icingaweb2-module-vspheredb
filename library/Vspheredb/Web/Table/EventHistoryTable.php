<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Util;
use ipl\Html\Attributes;
use ipl\Html\DeferredText;
use ipl\Html\FormattedString;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Zend_Db_Select;

class EventHistoryTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => ['common-table', 'event-history-table'],
        'data-base-target' => '_next',
    ];

    protected array $requiredUuids = [];

    protected array $vMotionEvents = [
        'VmFailedMigrateEvent',
        'MigrationEvent',
        'VmBeingMigratedEvent',
        'VmBeingHotMigratedEvent',
        'VmEmigratingEvent',
        'VmMigratedEvent',
    ];

    protected array $otherKnownEvents = [
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

    protected ?array $fetchedUuids = null;

    /** @var ?Datastore */
    protected ?Datastore $datastore = null;

    /** @var ?HostSystem */
    protected ?HostSystem $host = null;

    /** @var ?VirtualMachine */
    protected ?VirtualMachine $vm = null;

    /** @var string|array|null */
    protected string|array|null $eventType = null;

    /** @var ?UuidInterface */
    protected ?UuidInterface $parent = null;

    public function renderRow($row): HtmlElement
    {
        $this->renderDayIfNew($row->ts_event_ms / 1000);
        $content = [];

        if (null === $this->vm) {
            $content[] = 'VM: ';
            $content[] = Link::create(
                $this->deferredObjectName($row->vm_uuid),
                'vspheredb/vm/events',
                Util::uuidParams($row->vm_uuid)
            );
            $content[] = Html::tag('br');
        }

        if (in_array($row->event_type, $this->vMotionEvents)) {
            $content[] = 'Path: ';
            $content[] = $this->deferredVMotionPath($row);
            if ($row->user_name !== null && strlen($row->user_name) > 0) {
                $content[] = Html::tag('br');
                $content[] = sprintf('User: %s', $row->user_name);
            }
        } elseif (in_array($row->event_type, $this->otherKnownEvents)) {
            $content[] = new HtmlString(nl2br(new Text($row->full_message)));
        }
        $tr = $this::row([$content, DateFormatter::formatTime($row->ts_event_ms / 1000)]);

        $class = match ($row->event_type) {
            'VmFailedMigrateEvent', 'VmBeingClonedNoFolderEvent', 'VmCloneFailedEvent' => 'state migration-failed',
            'DrsVmMigratedEvent', 'VmMigratedEvent'                                    => 'state migrated',
            'VmBeingMigratedEvent', 'VmBeingHotMigratedEvent'                          => 'state migrating',
            'VmEmigratingEvent'                                                        => 'state emigrating',
            'VmResettingEvent', 'VmPoweredOffEvent'                                    => 'state poweredOff',
            'VmStartingEvent'                                                          => 'state starting',
            'VmPoweredOnEvent'                                                         => 'state poweredOn',
            'VmStoppingEvent'                                                          => 'state stopping',
            'VmSuspendedEvent'                                                         => 'event suspended',
            'VmReconfiguredEvent', 'VmClonedEvent', 'VmBeingClonedEvent'               => 'event reconfigured',
            'VmBeingCreatedEvent', 'VmBeingDeployedEvent'                              => 'event being-created',
            'VmCreatedEvent'                                                           => 'event created',
            default                                                                    => null
        };

        if ($class !== null) {
            $tr->addAttributes(Attributes::create(['class' => $class]));
        } else {
            $tr->add($this::td(Html::tag('pre', null, print_r($row, 1))));
        }

        return $tr->addAttributes(
            Attributes::create(['title' => sprintf('%s (%s)', $row->full_message, $row->event_type)])
        );
    }

    public function filterVm(VirtualMachine $vm): static
    {
        $this->vm = $vm;

        return $this;
    }

    public function filterHost(HostSystem $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function filterDatastore(Datastore $datastore): static
    {
        $this->datastore = $datastore;

        return $this;
    }

    public function filterEventType(string|array|null $type): static
    {
        if (is_array($type)) {
            $this->eventType = $type;
        } elseif ($type !== null && strlen($type)) {
            $this->eventType = $type;
        }

        return $this;
    }

    public function filterParent(?string $uuid): static
    {
        if ($uuid !== null && strlen($uuid)) {
            $this->parent = Uuid::fromString($uuid);
        }

        return $this;
    }

    /**
     * @param ?string $uuid
     *
     * @return string
     */
    protected function getUuidName(?string $uuid): string
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

    protected function fetchUuidNames(): void
    {
        $db = $this->db();
        if (empty($this->requiredUuids)) {
            $this->fetchedUuids = [];

            return;
        }

        $this->fetchedUuids = $db->fetchPairs(
            $db->select()
                ->from('object', ['uuid', 'object_name'])
                ->where('uuid IN (?)', DbUtil::quoteBinaryCompat(array_values($this->requiredUuids), $db))
        );
    }

    protected function timeSince(int $ms): ?string
    {
        return DateFormatter::timeAgo($ms);
    }

    /**
     * @return Zend_Db_Select
     */
    protected function prepareQuery(): Zend_Db_Select
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
                ['o' => 'object'],
                '(o.uuid = vh.vm_uuid OR o.uuid = vh.host_uuid OR o.uuid = vh.datastore_uuid)'
                . ' AND o.parent_uuid = ' . DbUtil::quoteBinaryCompat($this->parent->getBytes(), $this->db()),
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

    protected function deferredVMotionPath(object $row): DeferredText
    {
        $properties = [
            'host_uuid',
            'destination_host_uuid',
            'datastore_uuid',
            'destination_datastore_uuid',
        ];
        foreach ($properties as $property) {
            if ($row->$property !== null) {
                $this->requiredUuids[$row->$property] = $row->$property;
            }
        }

        $content = new DeferredText(function () use ($row) {
            return $this->showMotionPath($row);
        });

        return $content->setEscaped();
    }

    /**
     * @param ?string $uuid
     *
     * @return DeferredText
     */
    protected function deferredObjectName(?string $uuid): DeferredText
    {
        $this->requiredUuids[$uuid ?? ''] = $uuid;

        $content = new DeferredText(function () use ($uuid) {
            return $this->getUuidName($uuid);
        });

        return $content->setEscaped();
    }

    /**
     * @param object $row
     *
     * @return HtmlDocument
     */
    protected function showMotionPath(object $row): HtmlDocument
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

        if ($row->datastore_uuid !== $row->destination_datastore_uuid && $row->destination_datastore_uuid !== null) {
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
     * @param object $row
     *
     * @return FormattedString
     */
    protected function showHostToHostMigration(object $row): FormattedString
    {
        if ($row->event_type === 'VmEmigratingEvent') {
            return Html::sprintf(
                '%s %s ?',
                Link::create(
                    $this->getUuidName($row->host_uuid),
                    'vspheredb/host',
                    ['uuid' => Util::niceUuid($row->host_uuid)]
                ),
                Icon::create('right-big')
            );
        } else {
            return Html::sprintf(
                '%s %s %s',
                Link::create(
                    $this->getUuidName($row->host_uuid),
                    'vspheredb/host',
                    ['uuid' => Util::niceUuid($row->host_uuid)]
                ),
                Icon::create('right-big'),
                Link::create(
                    $this->getUuidName($row->destination_host_uuid),
                    'vspheredb/host',
                    ['uuid' => Util::niceUuid($row->destination_host_uuid)]
                )
            );
        }
    }

    /**
     * @param object $row
     *
     * @return FormattedString
     */
    protected function showDatastoreToDatastoreMigration(object $row): FormattedString
    {
        return Html::sprintf(
            '%s %s %s',
            Link::create(
                $this->getUuidName($row->datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => Util::niceUuid($row->datastore_uuid)]
            ),
            Icon::create('right-big'),
            Link::create(
                $this->getUuidName($row->destination_datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => Util::niceUuid($row->destination_datastore_uuid)]
            )
        );
    }

    /**
     * @param object $row
     *
     * @return FormattedString
     */
    protected function showToDatastoreMigration(object $row): FormattedString
    {
        return Html::sprintf(
            '%s %s',
            Icon::create('endtime'),
            Link::create(
                $this->getUuidName($row->destination_datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => Util::niceUuid($row->destination_datastore_uuid)]
            )
        );
    }

    /**
     * @param object $row
     *
     * @return FormattedString
     */
    protected function showFromDatastoreMigration(object $row): FormattedString
    {
        return Html::sprintf(
            '%s %s',
            Icon::create('starttime'),
            Link::create(
                $this->getUuidName($row->_datastore_uuid),
                'vspheredb/datastore',
                ['uuid' => Util::niceUuid($row->datastore_uuid)]
            )
        );
    }

    /**
     * @param object $row
     *
     * @return FormattedString
     */
    protected function showToHostMigration(object $row): FormattedString
    {
        return Html::sprintf(
            '%s %s',
            Icon::create('endtime'),
            Link::create(
                $this->getUuidName($row->destination_host_uuid),
                'vspheredb/host',
                ['uuid' => Util::niceUuid($row->destination_host_uuid)]
            )
        );
    }

    /**
     * @param object $row
     *
     * @return FormattedString
     */
    protected function showFromHostMigration(object $row): FormattedString
    {
        return Html::sprintf(
            '%s %s',
            Icon::create('starttime'),
            Link::create(
                $this->getUuidName($row->host_uuid),
                'vspheredb/host',
                ['uuid' => Util::niceUuid($row->host_uuid)]
            )
        );
    }
}
