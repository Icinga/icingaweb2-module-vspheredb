<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\PathLookup;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\VmsOnHostTable;
use ipl\Html\Html;
use ipl\Html\Link;
use ipl\Web\Widget\NameValueTable;

class HostController extends Controller
{
    /** @var int */
    protected $id;

    /** @var HostSystem */
    protected $host;

    /** @var VmsOnHostTable */
    protected $vmsTable;

    /** @var int */
    protected $vmCount;

    public function init()
    {
        $id = $this->id = (int) $this->params->getRequired('id');
        $this->host = HostSystem::load($id, $this->db());
        $this->vmsTable = VmsOnHostTable::create($this->host);
        $this->vmCount = count($this->vmsTable);

        $this->tabs()->add('host', [
            'label' => $this->translate('Host System'),
            'url' => 'vspheredb/host',
            'urlParams' => ['id' => $id]
        ])->add('vms', [
            'label' => sprintf(
                $this->translate('Virtual Machines (%d)'),
                $this->vmCount
            ),
            'url' => 'vspheredb/host/vms',
            'urlParams' => ['id' => $id]
        ]);
    }

    public function indexAction()
    {
        $id = $this->id;
        $host = $this->host;
        $object = $host->object();

        $this->tabs()->activate('host');
        $this->addTitle($object->get('object_name'));

        $table = new NameValueTable();

        $lookup = new PathLookup($this->db());
        $path = Html::tag('span', ['class' => 'dc-path'])->setSeparator(' > ');
        foreach ($lookup->getObjectNames($lookup->listPathTo($id, false)) as $parentId => $name) {
            $path->add(Link::create(
                $name,
                'vspheredb/overview/hosts',
                ['id' => $parentId],
                ['data-base-target' => '_main']
            ));
        }

        $table->addNameValuePairs([
            $this->translate('UUID')         => $host->get('sysinfo_uuid'),
            $this->translate('API Version')  => $host->get('product_api_version'),
            $this->translate('Product Name') => $host->get('product_full_name'),
            $this->translate('Memory')       => number_format($host->get('hardware_memory_size_mb'), 0, ',', '.') . ' MB',
            $this->translate('Path')         => $path,
            $this->translate('Power')        => $host->get('runtime_power_state'),
            $this->translate('BIOS Version') => $host->get('bios_version'),
            // $this->translate('BIOS Release Date') => $vm->get('bios_release_date'),
            $this->translate('Vendor')       => $host->get('sysinfo_vendor'),
            $this->translate('Model')        => $host->get('sysinfo_model'),
            $this->translate('CPU Model')    => $host->get('hardware_cpu_model'),
            $this->translate('CPU Packages') => $host->get('hardware_cpu_packages'),
            $this->translate('CPU Cores')    => $host->get('hardware_cpu_cores'),
            $this->translate('CPU Threads')  => $host->get('hardware_cpu_threads'),
            $this->translate('HBAs')         => $host->get('hardware_num_hba'),
            $this->translate('NICs')         => $host->get('hardware_num_nic'),
            $this->translate('Vms')          => Link::create(
                $this->vmCount,
                'vspheredb/host/vms',
                ['id' => $id]
            ),
        ]);

        $this->content()->add($table);
    }

    public function vmsAction()
    {
        $id = $this->params->getRequired('id');
        $host = HostSystem::load($id, $this->db());
        $object = $host->object();
        $this->tabs()->activate('vms');
        $this->addTitle($object->get('object_name'));

        $this->actions()->add(
            Link::create(
                $this->translate('Back to Host'),
                'vspheredb/host',
                ['id' => $id],
                ['class' => 'icon-left-big']
            )
        );

        $this->vmsTable->renderTo($this);
    }
}
