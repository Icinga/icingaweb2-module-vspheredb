<?php

namespace Icinga\Module\Vspheredb\Web\Table\Monitoring;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\ZfDb\Select;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\DbUtil;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Monitoring\CheckRunner;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Web\Widget\CheckPluginHelper;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use Ramsey\Uuid\Uuid;
use Zend_Db_Select;

class MonitoringRuleProblematicObjectTable extends ZfQueryBasedTable
{
    protected string $objectType;

    protected string $ruleSet;

    protected string $rule;

    /** @var CheckRunner */
    protected CheckRunner $runner;

    protected VCenter $vCenter;

    public function __construct(Db $db, Vcenter $vCenter, string $objectType, string $ruleSet, string $rule)
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
        return [$this->translate('Object')];
    }

    public function renderRow($row): array
    {
        $type = ObjectType::fromParam($this->objectType);
        $url = $type->url();
        $class = $type->class();

        $object = $class::load($row->uuid, $this->connection());
        $result = $this->runner->check($object);
        $label = $object->object()->get('object_name');
        if ($object instanceof VirtualMachine && $guest = $object->get('guest_host_name')) {
            if ($guest !== $label) {
                $label .= " ($guest)";
            }
        }

        $link = Link::create($label, $url, ['uuid' => Uuid::fromBytes($row->uuid)->toString()]);
        $output = $result->getOutput();
        $output = explode(PHP_EOL, $output);
        $output[0] .= ': LINK!TO!OBJECT';
        $output = CheckPluginHelper::colorizeOutput(implode(PHP_EOL, $output))->render();
        $output = preg_replace('/LINK!TO!OBJECT/', $link->render(), $output);

        return [[Html::tag('pre', ['class' => 'logOutput'], new HtmlString($output))]];
    }

    protected function prepareQuery(): Select|Zend_Db_Select
    {
        $objectTable = ObjectType::fromParam($this->objectType)->table();
        $db = $this->db();
        return $db->select()
            ->from(['p' => 'monitoring_rule_problem'], [
                'uuid'      => 'o.uuid',
                'rule_name' => 'p.rule_name'
            ])
            ->where('o.vcenter_uuid = ?', DbUtil::quoteBinaryCompat($this->vCenter->get('uuid'), $db))
            ->where('p.rule_name = ?', sprintf('%s/%s', $this->ruleSet, $this->rule))
            ->join(['o' => 'object'], 'o.uuid = p.uuid', [])
            ->join(['ot' => $objectTable], 'o.uuid = ot.uuid', [])
            ->order('p.current_state DESC')
            ->order('o.object_name');
    }
}
