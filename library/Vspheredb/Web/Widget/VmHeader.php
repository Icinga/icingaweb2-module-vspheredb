<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class VmHeader extends HtmlDocument
{
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
        $cpu = new CpuAbsoluteUsage(
            $vm->quickStats()->get('overall_cpu_usage'),
            $vm->get('hardware_numcpu')
        );
        $mem = new MemoryUsage(
            $vm->quickStats()->get('guest_memory_usage_mb'),
            $vm->get('hardware_memorymb'),
            $vm->quickStats()->get('host_memory_usage_mb')
        );
        $title = Html::tag('h1', $vm->object()->get('object_name'));
        $this->add([
            $cpu,
            $title,
            $mem
        ]);
    }
}
