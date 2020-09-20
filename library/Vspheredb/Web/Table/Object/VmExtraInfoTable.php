<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Widget\Link\MobLink;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;

class VmExtraInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    /** @var VCenter */
    protected $vCenter;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
        $this->prepend(new SubTitle($this->translate('Additional Information'), 'info-circled'));
        $this->vCenter = VCenter::load($vm->get('vcenter_uuid'), $vm->getConnection());
    }

    protected function getDb()
    {
        return $this->vm->getConnection();
    }

    /**
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function assemble()
    {
        $vm = $this->vm;

        $this->addNameValuePairs([
            $this->translate('UUID') => Html::tag('pre', $vm->get('bios_uuid')),
            $this->translate('Instance UUID') => Html::tag('pre', $vm->get('instance_uuid')),
            $this->translate('CPUs')   => $vm->get('hardware_numcpu'),
            $this->translate('Version') => $vm->get('version'),
        ]);
    }
}
