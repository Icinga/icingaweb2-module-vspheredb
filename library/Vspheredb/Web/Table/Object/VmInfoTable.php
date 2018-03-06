<?php

namespace Icinga\Module\Vspheredb\Web\Table\Object;

use dipl\Html\Html;
use dipl\Html\Link;
use dipl\Translation\TranslationHelper;
use dipl\Web\Widget\NameValueTable;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Widget\PowerStateRenderer;

class VmInfoTable extends NameValueTable
{
    use TranslationHelper;

    /** @var VirtualMachine */
    protected $vm;

    /** @var PathLookup */
    protected $pathLookup;

    /** @var VCenter */
    protected $vCenter;

    public function __construct(VirtualMachine $vm, VCenter $vCenter, PathLookup $lookup)
    {
        $this->vm = $vm;
        $this->pathLookup = $lookup;
        $this->vCenter = $vCenter;
    }

    protected function getDb()
    {
        return $this->vm->getConnection();
    }

    protected function assemble()
    {
        $vm = $this->vm;
        $uuid = $vm->get('uuid');
        if ($vm->get('annotation')) {
            $this->addNameValueRow($this->translate('Annotation'), $vm->get('annotation'));
        }

        $lookup = $this->pathLookup;
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($uuid, false)) as $parentUuid => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/vms',
                ['uuid' => bin2hex($parentUuid)],
                ['data-base-target' => '_main']
            ));
        }

        if ($guestName = $vm->get('guest_full_name')) {
            $guest = sprintf(
                '%s (%s)',
                $guestName,
                $vm->get('guest_id')
            );
        } else {
            $guest = '-';
        }
        $powerStateRenderer = new PowerStateRenderer();
        if ($vm->get('guest_id')) {
            $this->addNameValuePairs([
                $this->translate('Guest OS') => $guest,
                $this->translate('Guest IP') => $vm->get('guest_ip_address') ?: '-',
                $this->translate('Guest Hostname') => $vm->get('guest_host_name') ?: '-',
            ]);
        }

        $this->addNameValuePairs([
            // $this->translate('UUID') => $vm->get('bios_uuid'),
            // $this->translate('Instance UUID') => $vm->get('instance_uuid'),
            $this->translate('CPUs') => $vm->get('hardware_numcpu'),
            $this->translate('MO Ref') => $this->linkToVCenter($vm->object()->get('moref')),
            $this->translate('Memory')      => number_format($vm->get('hardware_memorymb'), 0, ',', '.') . ' MB',
            $this->translate('Is Template') => $vm->get('template') === 'y'
                ? $this->translate('true')
                : $this->translate('false'),
            $this->translate('Path') => $path,
            $this->translate('Power') => [
                $powerStateRenderer($vm->get('runtime_power_state')),
                sprintf(
                    '%s (Guest %s)',
                    $vm->get('guest_tools_running_status'),
                    $vm->get('guest_state')
                ),
            ],
            $this->translate('Resource Pool') => $lookup->linkToObject($vm->get('resource_pool_uuid')),
            $this->translate('Host') => $lookup->linkToObject($vm->get('runtime_host_uuid')),

        ]);
    }

    protected function linkToVCenter($moRef)
    {
        return Html::tag('a', [
            'href' => sprintf(
                'https://%s/mob/?moid=%s',
                $this->vCenter->getFirstServer()->get('host'),
                rawurlencode($moRef)
            ),
            'target' => '_blank',
            'title' => $this->translate('Jump to the Managed Object browser')
        ], $moRef);
    }
}
