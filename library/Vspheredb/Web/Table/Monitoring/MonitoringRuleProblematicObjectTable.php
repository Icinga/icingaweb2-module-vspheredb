<?php

namespace Icinga\Module\Vspheredb\Web\Table\Monitoring;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Monitoring\CheckRunner;
use Icinga\Module\Vspheredb\Monitoring\MonitoringRuleLookup;
use Icinga\Module\Vspheredb\Web\Widget\CheckPluginHelper;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;

class MonitoringRuleProblematicObjectTable extends ZfQueryBasedTable
{
    protected $objectType;
    protected $ruleSet;
    protected $rule;
    /** @var CheckRunner */
    protected $runner;
    protected $vCenter;

    public function __construct($db, $vCenter, $objectType, $ruleSet, $rule)
    {
        parent::__construct($db);
        $this->objectType = $objectType;
        $this->ruleSet = $ruleSet;
        $this->rule = $rule;
        $this->runner = new CheckRunner($db);
        $this->runner->setRuleSetName($ruleSet);
        $this->runner->setRuleName($rule);
        $this->vCenter = $vCenter;
    }

    public function getColumnsToBeRendered(): array
    {
        return [
            $this->translate('Object'),
        ];
    }

    public function renderRow($row)
    {
        $url = MonitoringRuleLookup::getUrlForObjectType($this->objectType);
        $class = MonitoringRuleLookup::getClassForObjectType($this->objectType);

        $object = $class::load($row->uuid, $this->connection());
        $result = $this->runner->check($object);
        $label = $object->object()->get('object_name');
        if ($object instanceof VirtualMachine && $guest = $object->get('guest_host_name')) {
            if ($guest !== $label) {
                $label .= " ($guest)";
            }
        }

        return [[
            Link::create($label, $url, [
                'uuid' => Uuid::fromBytes($row->uuid)->toString()
            ]),
            Html::tag('pre', ['class' => 'logOutput'], CheckPluginHelper::colorizeOutput($result->getOutput()))
        ]];
    }

    protected function prepareQuery()
    {
        $objectTable = MonitoringRuleLookup::getTableForObjectType($this->objectType);
        $db = $this->db();
        return $db->select()->from(
            ['p' => 'monitoring_rule_problem'],
            [
                'uuid' => 'o.uuid',
                'rule_name'  => 'p.rule_name',
            ]
        )
        ->where('o.vcenter_uuid = ?', DbUtil::quoteBinaryCompat($this->vCenter->get('uuid'), $db))
        ->where('p.rule_name = ?', sprintf('%s/%s', $this->ruleSet, $this->rule))
        ->join(['o' => 'object'], 'o.uuid = p.uuid', [])
        ->join(['ot' => $objectTable], 'o.uuid = ot.uuid', [])
        ->order('o.object_name');
    }
}
