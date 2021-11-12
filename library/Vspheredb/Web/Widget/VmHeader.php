<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class VmHeader extends HtmlDocument
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    /** @var VmQuickStats */
    protected $quickStats;

    public function __construct(VirtualMachine $vm, VmQuickStats $quickStats)
    {
        $this->vm = $vm;
        $this->quickStats = $quickStats;
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function assemble()
    {
        $vm = $this->vm;
        $powerState = $vm->get('runtime_power_state');
        $renderer = new PowerStateRenderer();
        if ($vm->get('template') === 'y') {
            $cpu = Html::tag('div', [
                'class' => 'vm-template'
            ], Icon::create('upload', [
                'title' => $this->translate('This is a template'),
                'class' => [ 'state' ]
            ]));

            $mem = $this->translate('This is a template');
        } elseif ($powerState !== 'poweredOn') {
            $cpu = Html::tag('div', [
                'class' => 'cpu off',
                // 'style' => 'font-size: 3em; width: 1em; height: 1em; display: inline-block;',
            ], $renderer($powerState));
            $mem = $renderer->getPowerStateDescription($powerState);
        } else {
            $cpu = new CpuAbsoluteUsage(
                $this->quickStats->get('overall_cpu_usage'),
                $vm->get('hardware_numcpu')
            );
            $mem = new MemoryUsage(
                $this->quickStats->get('guest_memory_usage_mb'),
                $vm->get('hardware_memorymb'),
                $this->quickStats->get('host_memory_usage_mb')
            );
        }
        $title = Html::tag('h1', $vm->object()->get('object_name'));
        $this->add([
            $cpu,
            $title,
            $mem
        ]);
    }
}
