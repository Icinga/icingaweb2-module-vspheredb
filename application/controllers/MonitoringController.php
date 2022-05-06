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
use Icinga\Web\Notification;
use ipl\Html\Html;

class MonitoringController extends Controller
{
    public function init()
    {
        $this->assertPermission('vspheredb/admin');
        parent::init();
        $action = $this->getRequest()->getActionName();
        if (preg_match('/tree$/', $action) || $action === 'index') {
            $this->tabs()->add('index', [
                'label' => $this->translate('Monitoring'),
                'url' => 'vspheredb/monitoring'
            ])->add('hosttree', [
                'label' => $this->translate('Hosts'),
                'url' => 'vspheredb/monitoring/hosttree'
            ])->add('vmtree', [
                'label' => $this->translate('Virtual Machines'),
                'url' => 'vspheredb/monitoring/vmtree'
            ])->add('datastoretree', [
                'label' => $this->translate('Datastores'),
                'url' => 'vspheredb/monitoring/datastoretree'
            ])->activate($action);
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
        $this->content()->addAttributes([
            'class' => 'overview-chapter'
        ]);
        $this->content()->add([
            Hint::info(Html::sprintf($this->translate(
                'The Icinga Module for vSphere® ships a lot of data, state and sensor values.'
                . ' If you want to define related Icinga Service checks for Alarming reasons,'
                . ' Monitoring Rules are %s.'
            ), Html::tag('strong', $this->translate('the way to go')))),
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
                Link::create($this->translate('documentation'), '#') // TODO
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
        $this->addTitle($this->translate('Monitoring'));
        $tree = new MonitoringRulesTree($this->db(), $chosenType);
        $this->content()->add(new MonitoringRulesTreeRenderer($tree, "vspheredb/monitoring/${chosenType}rules"));
    }

    public function showType($chosenType)
    {
        $this->addSingleTab($this->translate('Rules'));
        $uuid = $this->params->get('uuid');
        $db = $this->db();
        if ($uuid === null) {
            $binaryUuid = '';
            $title = $this->translate('Global VM Monitoring Rules');
        } else {
            $binaryUuid = hex2bin($uuid);
            if (strlen($binaryUuid) === 16) {
                $vCenter = VCenter::load($binaryUuid, $db);
                $title = sprintf(
                    $this->translate('vCenter VM Monitoring Rules: %s'),
                    $vCenter->get('name')
                );
            } else {
                $parent = ManagedObject::load($binaryUuid, $this->db());
                $vCenter = VCenter::load($parent->get('vcenter_uuid'), $db);
                if ($parent->getNumericLevel() === 2) {
                    $dataCenter = ManagedObject::load($parent->get('parent_uuid'), $db);
                    $title = sprintf(
                        $this->translate('DataCenter VM Monitoring Rules: %s (%s)'),
                        $dataCenter->get('object_name'),
                        $vCenter->get('name')
                    );
                } else {
                    $title = sprintf(
                        $this->translate('Folder VM Monitoring Rules: %s (%s)'),
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
            } elseif ($form->hasBeenModified()) {
                Notification::success(sprintf($this->translate('Settings for %s have been modified'), $title));
            }
            if ($form->hasBeenCreated()) {
                Notification::success(sprintf($this->translate('Settings for %s have been stored'), $title));
            } elseif ($form->hasBeenDeleted()) {
                Notification::success(sprintf($this->translate('Settings for %s have been removed'), $title));
            }
            // $this->redirectNow($this->url());
        });
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
        $form->addElement('submit', 'submit', [
            'label' => $this->translate('Store')
        ]);
    }
}
