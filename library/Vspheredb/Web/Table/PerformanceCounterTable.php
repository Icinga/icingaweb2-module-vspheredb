<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Url;
use Icinga\Module\Vspheredb\DbObject\VCenter;

class PerformanceCounterTable extends BaseTable
{
    /** @var VCenter */
    protected $vCenter;

    protected $searchColumns = [
        'counter_key',
        'name',
        'group_name',
        'unit_name',
        'label',
        'summary',
        'stats_type',
        'rollup_type',
        // TODO: disabled, Director breaks this right now for security reasons
        // "(c.group_name || '.' || c.name)",
    ];

    public function __construct($db, Url $url = null, VCenter $vCenter = null)
    {
        $this->vCenter = $vCenter;
        parent::__construct($db, $url);
    }

    protected function initialize()
    {
        $this->addAvailableColumns([
            $this->createColumn('key', $this->translate('Key'), [
                'group_name',
                'name'
            ])->setRenderer(function ($row) {
                return sprintf(
                    '%s.%s',
                    $row->group_name,
                    $row->name
                );
            }),
            $this->createColumn('name', $this->translate('Name'), [
                'group_name',
                'name'
            ])->setRenderer(function ($row) {
                return sprintf(
                    '%s.%s',
                    $row->label,
                    $row->summary
                );
            }),
            $this->createColumn('unit_name', $this->translate('Unit')),
            $this->createColumn('stats_type', $this->translate('Stats')),
            $this->createColumn('rollup_type', $this->translate('Rollup')),
            $this->createColumn('counter_key', $this->translate('ID')),
        ]);
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['c' => 'performance_counter']
        );
        // ->order('group_name')->order('name')->order('unit_name');

        if ($this->vCenter !== null) {
            $query->where('vcenter_uuid = ?', $this->vCenter->getUuid());
        }

        return $query;
    }
}
