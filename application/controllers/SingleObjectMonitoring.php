<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Link;
use gipfl\Web\Widget\Hint;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\Monitoring\CheckRunner;
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
            'style' => 'font-size: 1.25em'
        ], $this->colorizeOutput($result->getOutput())));
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

    protected function colorizeOutput(string $output): HtmlString
    {
        $pattern = '/\[(OK|WARNING|CRITICAL|UNKNOWN)]\s/';
        $safeString = (new Text($output))->render();
        $safeString = preg_replace_callback($pattern, function ($match) {
            $state = strtolower($match[1]);
            return Html::tag('span', ['class' => ['check-result', "state-$state"]], $match[1]) . ' ';
        }, $safeString);
        return new HtmlString($safeString);
    }
}
