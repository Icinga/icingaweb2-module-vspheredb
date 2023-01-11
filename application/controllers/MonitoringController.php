<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\Web\Widget\Hint;
use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\RuleSetRegistry;
use Icinga\Module\Vspheredb\Monitoring\Rule\Enum\ObjectType;
use Icinga\Module\Vspheredb\Monitoring\Rule\InheritedSettings;
use Icinga\Module\Vspheredb\Monitoring\Rule\MonitoringRuleSet;
use Icinga\Module\Vspheredb\Monitoring\Rule\MonitoringRulesTree;
use Icinga\Module\Vspheredb\Monitoring\Rule\MonitoringRulesTreeRenderer;
use Icinga\Module\Vspheredb\Monitoring\Rule\RuleForm;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\Monitoring\MonitoringRuleProblematicObjectTable;
use Icinga\Module\Vspheredb\Web\Table\Monitoring\MonitoringRuleProblemTable;
use Icinga\Module\Vspheredb\Web\Widget\Documentation;
use Icinga\Web\Notification;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class MonitoringController extends Controller
{
    use AsyncControllerHelper;

    public function init()
    {
        parent::init();
        $action = $this->getRequest()->getActionName();
        if (preg_match('/tree$/', $action) || $action === 'index' || $action === 'configuration') {
            $tabs = $this->tabs();
            $tabs->add('index', [
                'label' => $this->translate('Monitoring'),
                'url' => 'vspheredb/monitoring'
            ]);
            if ($this->hasPermission('vspheredb/admin')) {
                $tabs->add('configuration', [
                    'label' => $this->translate('Configuration'),
                    'url' => 'vspheredb/monitoring/configuration'
                ])->add('hosttree', [
                    'label' => $this->translate('Hosts'),
                    'url' => 'vspheredb/monitoring/hosttree'
                ])->add('vmtree', [
                    'label' => $this->translate('Virtual Machines'),
                    'url' => 'vspheredb/monitoring/vmtree'
                ])->add('datastoretree', [
                    'label' => $this->translate('Datastores'),
                    'url' => 'vspheredb/monitoring/datastoretree'
                ]);
            }
            $tabs->activate($action);
        }
        if (preg_match('/tree$/', $action)) {
            $this->actions()->add(
                Link::create($this->translate('Back to overview'), 'vspheredb/monitoring', null, [
                    'class' => 'icon-left-small'
                ])
            );
            $this->content()->add(Hint::info($this->translate(
                'Select any folder (or the root node) to define (and override) Monitoring Rules'
                . ' for all related child nodes'
            )));
        }
    }

    public function indexAction()
    {
        $this->addTitle($this->translate('Monitoring Rules'));
        $this->setAutorefreshInterval(20);
        $table = new MonitoringRuleProblemTable($this->db()->getDbAdapter());
        $this->getRestrictionHelper()->restrictTable($table);
        $table->renderTo($this);
    }

    public function problemsAction()
    {
        $this->addSingleTab($this->translate('Current Problems'));
        $vCenter = $this->requireVCenter();
        $this->getRestrictionHelper()->assertAccessToVCenterUuidIsGranted($vCenter->get('instance_uuid'));
        $this->setAutorefreshInterval(20);
        $objectType = $this->params->getRequired('objectType');
        $ruleSet = $this->params->getRequired('ruleSet');
        $rule = $this->params->getRequired('rule');
        $this->addTitle(sprintf('%s / %s (%s)', $ruleSet, $rule, $objectType));
        $table = new MonitoringRuleProblematicObjectTable($this->db(), $vCenter, $objectType, $ruleSet, $rule);
        $table->renderTo($this);
    }

    public function configurationAction()
    {
        $this->assertPermission('vspheredb/admin');
        $this->addTitle($this->translate('Monitoring Rules'));
        $this->content()->addAttributes([
            'class' => 'overview-chapter'
        ]);
        $this->content()->add([
            Hint::info(Html::sprintf($this->translate(
                'The Icinga vSphere%s Integration ships a lot of data, state and sensor values.'
                . ' If you want to define related Icinga Service checks for Alarming reasons,'
                . ' Monitoring Rules are %s.'
            ), '®', Html::tag('strong', $this->translate('the way to go')))),
            Hint::info(Html::sprintf($this->translate(
                'Instead of checking every single Disk, just define rules (and exemptions'
                . ' from those) for %s.'
            ), Html::tag('strong', $this->translate('all of them')))),
            Html::tag('h2', $this->translate('Defining Rules')),
            Html::tag('p', Html::sprintf(
                $this->translate('Define rules for %s, %s and %s'),
                Link::create($this->translate('Host Systems'), 'vspheredb/monitoring/hosttree'),
                Link::create($this->translate('Virtual Machines'), 'vspheredb/monitoring/vmtree'),
                Link::create($this->translate('Datastores'), 'vspheredb/monitoring/datastoretree')
            )),
            Html::tag('h2', $this->translate('Defining Check Commands')),
            Html::tag('p', Html::sprintf(
                $this->translate('Check our %s for instructions of how to set them up'),
                Documentation::link(
                    $this->translate('Documentation'),
                    'vspheredb',
                    '32-Monitoring_Rules',
                    $this->translate('Icinga vSphereDB Check Commands')
                )
            ))
        ]);
    }

    public function hostrulesAction()
    {
        $this->showType(ObjectType::HOST_SYSTEM);
    }

    public function hosttreeAction()
    {
        $this->showTree(ObjectType::HOST_SYSTEM);
    }

    public function vmrulesAction()
    {
        $this->showType(ObjectType::VIRTUAL_MACHINE);
    }

    public function vmtreeAction()
    {
        $this->showTree(ObjectType::VIRTUAL_MACHINE);
    }

    public function datastorerulesAction()
    {
        $this->showType(ObjectType::DATASTORE);
    }

    public function datastoretreeAction()
    {
        $this->showTree(ObjectType::DATASTORE);
    }

    public function showTree($chosenType)
    {
        $this->assertPermission('vspheredb/admin');
        $this->addTitle($this->translate('Monitoring'));
        $tree = new MonitoringRulesTree($this->db(), $chosenType);
        $this->content()->add(new MonitoringRulesTreeRenderer($tree, "vspheredb/monitoring/${chosenType}rules"));
    }

    public function showType($chosenType)
    {
        $this->assertPermission('vspheredb/admin');
        $this->addSingleTab($this->translate('Rules'));
        $uuid = $this->params->get('uuid');
        $db = $this->db();
        $objectTypeLabel = $this->getTypeLabelForObjectType($chosenType);
        if ($uuid === null) {
            $binaryUuid = '';
            $title = sprintf($this->translate('Global Monitoring Rules for %s'), $objectTypeLabel);
        } else {
            $binaryUuid = Uuid::fromString($uuid)->getBytes();
            if (VCenter::exists($binaryUuid, $db)) {
                $vCenter = VCenter::loadWithUuid($binaryUuid, $db);
                $title = sprintf(
                    $this->translate('vCenter Monitoring Rules for %s: %s'),
                    $objectTypeLabel,
                    $vCenter->get('name')
                );
            } else {
                $parent = ManagedObject::load($binaryUuid, $this->db());
                $vCenter = VCenter::load($parent->get('vcenter_uuid'), $db);
                if ($parent->getNumericLevel() === 2) {
                    $dataCenter = ManagedObject::load($parent->get('parent_uuid'), $db);
                    $title = sprintf(
                        $this->translate('DataCenter Monitoring Rules for %s: %s (%s)'),
                        $objectTypeLabel,
                        $dataCenter->get('object_name'),
                        $vCenter->get('name')
                    );
                } else {
                    $title = sprintf(
                        $this->translate('Folder Monitoring Rules for %s: %s (%s)'),
                        $objectTypeLabel,
                        $parent->get('object_name'),
                        $vCenter->get('name')
                    );
                }
            }
        }
        $this->addTitle($title);
        $tree = new MonitoringRulesTree($db, $chosenType);
        $storedConfig = MonitoringRuleSet::loadOptionalForUuid($binaryUuid, $chosenType, $db);
        $inherited = InheritedSettings::loadFor($binaryUuid, $tree, $db);
        $inherited->setInternalDefaults(RuleSetRegistry::default());
        $form = new RuleForm($chosenType, $binaryUuid, $db, $inherited, $storedConfig);
        $form->on(RuleForm::ON_SUCCESS, function (RuleForm $form) use ($title) {
            if ($form->hasNotBeenModified()) {
                Notification::info($this->translate('No change has been applied'));
                $this->redirectNow($this->url());
            }

            try {
                if ($this->syncRpcCall('db.refreshMonitoringRuleProblems')) {
                    Notification::success($this->translate('Current Problems have been recalculated'));
                } else {
                    Notification::info($this->translate(
                        'Current problems have NOT been recalculated, they will be applied with a short delay'
                    ));
                }
            } catch (\Exception $e) {
                Notification::info(
                    $this->translate(
                        'Error when triggering problem recalculation, changes will be applied with a short delay'
                    ) . ': ' . $e->getMessage()
                );
            }
            if ($form->hasNotBeenModified()) {
                Notification::info($this->translate('No change has been applied'));
            } elseif ($form->hasBeenModified()) {
                Notification::success(sprintf($this->translate('Settings for %s have been modified'), $title));
            }
            if ($form->hasBeenCreated()) {
                Notification::success(sprintf($this->translate('Settings for %s have been stored'), $title));
            } elseif ($form->hasBeenDeleted()) {
                Notification::success(sprintf($this->translate('Settings for %s have been removed'), $title));
            }
            $this->redirectNow($this->url());
        });
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
        $form->addElement('submit', 'submit', [
            'label' => $this->translate('Store')
        ]);
    }

    protected function getTypeLabelForObjectType(string $type): string
    {
        switch ($type) {
            case 'host':
                return $this->translate('Host Systems');
            case 'vm':
                return $this->translate('Virtual Machines');
            case 'datastore':
                return $this->translate('Datastores');
        }

        throw new RuntimeException("Unexpected object type: '$type'");
    }
}
