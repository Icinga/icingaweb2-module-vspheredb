<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use ipl\Html\Html;

class HostSensorsTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    protected $searchColumns = [
        'name',
        'sensor_type',
    ];

    /** @var HostSystem */
    protected $host;

    protected $lastType;

    protected $summaries;

    public function renderRow($row)
    {
        $this->renderTypeIfNew($row->sensor_type);
        return static::row([
            $this->renderHealthState($row->health_state),
            $row->name,
            $this->renderCurrentMeasurement($row),
        ]);
    }

    /**
     * @param $type
     * @throws \Icinga\Exception\IcingaException
     */
    protected function renderTypeIfNew($type)
    {
        if ($this->lastType !== $type) {
            $summary = $this->getSummaryByType($type);

            $div = Html::tag('div', ['class' => 'object-summaries']);
            $title = [$div, ucfirst($type)];
            foreach ($summary as $state => $count) {
                if ($count > 0) {
                    $div->add($this->makeHealthStateBadge($state, $count));
                }
            }

            $this->nextHeader()->add(
                $this::th($title, [
                    'colspan' => 3,
                    'class'   => 'table-header-day'
                ])
            );

            $this->lastType = $type;
            $this->nextBody();
        }
    }

    protected function makeHealthStateBadge($state, $count)
    {
        return Link::create($count, '/', null, ['class' => ['state', $state]]);
    }

    /**
     * @param $type
     * @return mixed
     * @throws \Icinga\Exception\IcingaException
     */
    protected function getSummaryByType($type)
    {
        if ($this->summaries === null) {
            $this->summaries = $this->fetchSummaries();
        }

        return $this->summaries[$type];
    }

    protected function renderHealthState($state)
    {
        switch ($state) {
            case 'green':
                return Icon::create('ok', ['class' => ['state', $state]]);
            case 'red':
            case 'yellow':
                return Icon::create('attention-alt', ['class' => ['state', $state]]);
            case 'unknown':
                return Icon::create('help', ['class' => ['state gray']]);
            default:
                return $state;
        }
    }

    public function renderSummaries()
    {

    }

    protected function renderCurrentMeasurement($row)
    {
        if ($row->base_units === null) {
            return '-';
        }

        return sprintf(
            '%s %s',
            $row->current_reading * pow(10, $row->unit_modifier),
            $row->base_units
        );
    }

    public function filterHost(HostSystem $host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return array
     * @throws \Icinga\Exception\IcingaException
     */
    public function fetchSummaries()
    {
        // Well... ROLLUP would help.
        $db = $this->db();

        $sums = [];
        $query = $db->select()->from(['hs' => 'host_sensor'], [
            'sensor_type'  => 'sensor_type',
            'health_state' => 'health_state',
            'cnt'          => 'COUNT(*)',
        ])
            ->group('sensor_type')
            ->group('health_state')
            ->order('sensor_type')
            ->order('health_state');
        if ($this->host) {
            $query->where('host_uuid = ?', $this->host->get('uuid'));
        }

        $rows = $db->fetchAll($query);
        foreach ($rows as $row) {
            if (! array_key_exists($row->sensor_type, $sums)) {
                $sums[$row->sensor_type] = [
                    'green' => 0,
                    'yellow' => 0,
                    'unknown' => 0,
                    'red' => 0,
                ];
            }

            $sums[$row->sensor_type][$row->health_state] += $row->cnt;
        }

        return $sums;
    }

    /**
     * @return \Zend_Db_Select
     * @throws \Icinga\Exception\IcingaException
     */
    protected function prepareQuery()
    {
        $query = $this->db()->select()->from([
            'hpd' => 'host_sensor'
        ])->order('sensor_type')->order('name')->limit(1000);

        $query->where('base_units IS NOT NULL');
        $query->where('health_state != ?', 'unknown');

        if ($this->host) {
            $query->where('host_uuid = ?', $this->host->get('uuid'));
        }

        return $query;
    }
}
