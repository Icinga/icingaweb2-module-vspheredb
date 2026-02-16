<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\Extension\ZfSortablePriority;
use gipfl\ZfDb\Select;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use Zend_Db_Select;

class MonitoredObjectMappingTable extends BaseTable
{
    use ZfSortablePriority;

    protected string $keyColumn = 'id';

    protected string $priorityColumn = 'priority';

    protected $defaultAttributes = [
        'class' => ['common-table', 'table-row-selectable'],
        'data-base-target' => '_next',
    ];

    protected function initialize(): void
    {
        $this->addAvailableColumns([
            (new SimpleColumn('source', $this->translate('Source'), [
                'source_type'          => 'mc.source_type',
                'source_resource_name' => 'mc.source_resource_name',
                'id'                   => 'mc.id',
                'priority'             => 'mc.priority',
            ]))->setRenderer(function ($row) {
                return Link::create(sprintf(
                    '%s: %s',
                    $row->source_type,
                    $row->source_resource_name
                ), 'vspheredb/configuration/monitoringconfig', [
                    'id' => $row->id
                ], [
                    'data-base-target' => '_next'
                ]);
            }),
            (new SimpleColumn('host_mapping', $this->translate('Host Mapping'), [
                'host_property'            => 'mc.host_property',
                'monitoring_host_property' => 'mc.monitoring_host_property',
            ]))->setRenderer(function ($row) {
                if ($row->host_property === null) {
                    return null;
                } else {
                    return sprintf(
                        '%s -> %s',
                        $row->monitoring_host_property,
                        $row->host_property
                    );
                }
            }),
            (new SimpleColumn('vm_mapping', $this->translate('VM Mapping'), [
                'vm_property'                 => 'mc.vm_property',
                'monitoring_vm_host_property' => 'mc.monitoring_vm_host_property',
            ]))->setRenderer(function ($row) {
                if ($row->host_property === null) {
                    return null;
                } else {
                    return sprintf(
                        '%s -> %s',
                        $row->monitoring_vm_host_property,
                        $row->vm_property
                    );
                }
            }),
        ]);
    }

    // cloned from ZfSortablePriority, added data-base-target
    protected function xaddSortPriorityButtons(BaseHtmlElement $tr, object $row): BaseHtmlElement
    {
        $tr->add(
            Html::tag(
                'td',
                ['data-base-target' => '_self'],
                $this->createUpDownButtons($row->{$this->getKeyColumn()})
            )
        );

        return $tr;
    }

    public function renderRow($row): BaseHtmlElement
    {
        return $this->xaddSortPriorityButtons(parent::renderRow($row), $row);
    }

    public function render(): string
    {
        return $this->renderWithSortableForm();
    }

    public function prepareQuery(): Select|Zend_Db_Select
    {
        return $this->db()->select()->from(
            ['mc' => 'monitoring_connection'],
            $this->getRequiredDbColumns()
        )->order('priority');
    }
}
