<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\Extension\ZfSortablePriority;

class MonitoredObjectMappingTable extends BaseTable
{
    use ZfSortablePriority;

    protected $keyColumn = 'id';

    protected $priorityColumn = 'priority';

    protected function initialize()
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
                    'priority' => $row->priority
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
    public function renderRow($row)
    {
        return $this->addSortPriorityButtons(
            parent::renderRow($row),
            $row
        );
    }

    public function render()
    {
        return $this->renderWithSortableForm();
    }

    public function prepareQuery()
    {
        return $this->db()->select()->from(
            ['mc' => 'monitoring_connection'],
            $this->getRequiredDbColumns()
        );
    }
}
