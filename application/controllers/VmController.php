<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\VmConfig;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\VmDatastoresTable;
use ipl\Html\Html;
use ipl\Html\Link;
use ipl\Web\Widget\NameValueTable;

class VmController extends Controller
{
    public function indexAction()
    {
        $id = $this->params->getRequired('id');
        $vm = VmConfig::load($id, $this->db());
        $object = $vm->object();

        $this
            ->addSingleTab($this->translate('Virtual Machine'))
            ->addTitle($object->get('object_name'));

        $table = new NameValueTable();
        if ($vm->get('annotation')) {
            $table->addNameValueRow($this->translate('Annotation'), $vm->get('annotation'));
        }

        $lookup = new PathLookup($this->db());
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($id, false)) as $parentId => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/overview/vms',
                ['id' => $parentId],
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

        $table->addNameValuePairs([
            $this->translate('UUID') => $vm->get('bios_uuid'),
            $this->translate('Instance UUID') => $vm->get('instance_uuid'),
            $this->translate('CPUs') => $vm->get('hardware_numcpu'),
            $this->translate('Memory')      => number_format($vm->get('hardware_memorymb'), 0, ',', '.') . ' MB',
            $this->translate('Is Template') => $vm->get('template') === 'y'
                ? $this->translate('true')
                : $this->translate('false'),
            $this->translate('Path') => $path,
            $this->translate('Power') => $vm->get('runtime_power_state'),
            $this->translate('Resource Pool') => $lookup->linkToObject($vm->get('resource_pool_id')),
            $this->translate('Host') => $lookup->linkToObject($vm->get('runtime_host_id')),
            $this->translate('Guest State') => $vm->get('guest_state'),
            $this->translate('Guest Tools') => $vm->get('guest_tools_running_status'),
            $this->translate('Guest OS') => $guest,
            $this->translate('Guest IP') => $vm->get('guest_ip_address') ?: '-',
            $this->translate('Guest Hostname') => $vm->get('guest_host_name') ?: '-',
        ]);

        $datastores = VmDatastoresTable::create($vm);
        $this->content()->add([$table, $datastores]);
    }
}
