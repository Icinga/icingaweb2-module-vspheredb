<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\Web\Widget\Hint;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\CheckRunner;
use Icinga\Module\Vspheredb\Web\Widget\CheckPluginHelper;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Html\Text;

trait SingleObjectMonitoring
{
    protected function showMonitoringDetails(BaseDbObject $object)
    {
        $inspect = $this->params->get('inspect');
        $runner = new CheckRunner($this->db());
        if ($inspect) {
            $runner->enableInspection();
        }
        $result = $runner->check($object);
        $this->content()->add($this->createMonitoringHint($object, $inspect));
        $this->actions()->add($this->createMonitoringInspectionLink($inspect));
        $this->content()->add(Html::tag('pre', [
            'class' => 'logOutput',
            'style' => 'font-size: 1.15em'
        ], CheckPluginHelper::colorizeOutput($result->getOutput())));
        if ($this->Auth()->hasPermission('vspheredb/admin')) {
            switch ($object->getTableName()) {
                case 'virtual_machine':
                    $tab = 'vmtree';
                    break;
                case 'host_system':
                    $tab = 'hosttree';
                    break;
                case 'datastore':
                    $tab = 'datastoretree';
                    break;
                default:
                    $tab = null;
            }
            if ($tab) {
                $this->content()->add(Html::tag('p', [Html::tag('br'), Html::sprintf(
                    $this->translate('Please click %s to configure related Monitoring Rules'),
                    Link::create($this->translate('here'), "vspheredb/monitoring/$tab")
                )]));
            }
        }
    }

    protected function createMonitoringHint(BaseDbObject $object, ?bool $inspect = null): Hint
    {
        return Hint::info(Html::sprintf(
            $this->translate(
                'If you want to get Alarms for this object, you can configure'
                . ' related Icinga Service (or Host) Checks: %s'
                . ' Doing so can be automated via an Icinga Director Import Source'
            ),
            Html::tag('pre', [
                'class' => 'logOutput'
            ], sprintf(
                'icingacli vspheredb check %s --name %s%s',
                CheckRunner::getCheckTypeForObject($object),
                escapeshellarg($object->object()->get('object_name')),
                $inspect ? ' --inspect' : ''
            ))
        ));
    }

    protected function createMonitoringInspectionLink(?bool $inspect = null): Link
    {
        if ($inspect) {
            return Link::create(
                $this->translate('Hide Inspection'),
                $this->url()->without('inspect'),
                null,
                ['class' => 'icon-left-big']
            );
        } else {
            return Link::create(
                $this->translate('Inspect'),
                $this->url()->with('inspect', true),
                null,
                ['class' => 'icon-services']
            );
        }
    }
}
