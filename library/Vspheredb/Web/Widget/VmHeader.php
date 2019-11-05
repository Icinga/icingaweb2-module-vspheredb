<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class VmHeader extends HtmlDocument
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function assemble()
    {
        $vm = $this->vm;
        $powerState = $vm->get('runtime_power_state');
        $renderer = new PowerStateRenderer();
        if ($powerState !== 'poweredOn') {
            $cpu = Html::tag('div', [
                'class' => 'cpu off',
                // 'style' => 'font-size: 3em; width: 1em; height: 1em; display: inline-block;',
            ], $renderer($powerState));
            $mem = $renderer->getPowerStateDescription($powerState);
        } else {
            $cpu = new CpuAbsoluteUsage(
                $vm->quickStats()->get('overall_cpu_usage'),
                $vm->get('hardware_numcpu')
            );
            $mem = new MemoryUsage(
                $vm->quickStats()->get('guest_memory_usage_mb'),
                $vm->get('hardware_memorymb'),
                $vm->quickStats()->get('host_memory_usage_mb')
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
