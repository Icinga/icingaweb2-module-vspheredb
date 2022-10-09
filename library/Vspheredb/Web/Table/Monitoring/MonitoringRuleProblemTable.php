<?php

namespace Icinga\Module\Vspheredb\Web\Table\Monitoring;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\Util;
use ipl\Html\Html;

class MonitoringRuleProblemTable extends ZfQueryBasedTable
{
    protected $formerVCenter = null;

    public function getColumnsToBeRendered(): array
    {
        return [
            $this->translate('VCenter'),
            $this->translate('Problems / Monitoring Rule'),
        ];
    }

    public function renderRow($row)
    {
        if ($row->vcenter_name === $this->formerVCenter) {
            $row->vcenter_name = null;
        } else {
            $this->formerVCenter = $row->vcenter_name;
        }

        $states = [];
        foreach (['critical', 'unknown', 'warning'] as $state) {
            $property = "cnt_$state";
            if ($row->$property > 0) {
                if (! empty($states)) {
                    $states[] = ' ';
                }
                $states[] = Html::tag('span', ['class' => [
                    'badge',
                    "state-$state"
                ]], $row->$property);
            }
            unset($row->$property);
        }
        $parts = explode('/', $row->object_rule_name);
        $objectType = array_shift($parts);
        $ruleSet = array_shift($parts);
        $rule = array_shift($parts);
        $row->object_rule_name = [Html::tag('span', [
            'style' => 'width: 8em; display: inline-block'
        ], $states), Link::create($row->object_rule_name, 'vspheredb/monitoring/problems', [
            'vcenter'    => Util::niceUuid($row->vcenter_uuid),
            'objectType' => $objectType,
            'ruleSet'    => $ruleSet,
            'rule'       => $rule,
        ])];
        unset($row->vcenter_uuid);

        return (array) $row;
    }

    protected function prepareQuery()
    {
        $db = $this->db();
        return $db->select()->from(
            ['p' => 'monitoring_rule_problem'],
            [
                'vcenter_uuid' => 'vc.instance_uuid',
                'vcenter_name' => 'vc.name',
                // 'object_type' => 'o.object_type',
                // 'rule_name' => 'p.rule_name',
                'object_rule_name' => "o.object_type || '/' || p.rule_name",
                'cnt_critical' => "SUM(CASE WHEN p.current_state = 'CRITICAL' THEN 1 ELSE 0 END)",
                'cnt_unknown' => "SUM(CASE WHEN p.current_state = 'UNKNOWN' THEN 1 ELSE 0 END)",
                'cnt_warning' => "SUM(CASE WHEN p.current_state = 'WARNING' THEN 1 ELSE 0 END)",
            ]
        )
            ->where('p.rule_name LIKE ?', '%/%')
        ->join(['o' => 'object'], 'o.uuid = p.uuid', [])
        ->join(['vc' => 'vcenter'], 'o.vcenter_uuid = vc.instance_uuid', [])
            ->group('vc.name')
            ->group('o.object_type')
            ->group('p.rule_name')
            ->order('vc.name')
            ->order('o.object_type')
            ->order('p.rule_name')
            ;
    }
}
