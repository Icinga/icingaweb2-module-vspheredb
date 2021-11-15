<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\PerformanceData\IcingaRrd\RrdImg;
use Icinga\Module\Vspheredb\Web\Widget\GrafanaVmPanel;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;

class VmNetworkAdapterTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    /** @var VirtualMachine */
    protected $vm;

    /** @var string */
    protected $moref;

    protected $withPerfImages = false;

    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
        $this->moref = $this->vm->object()->get('moref');
        $this->prepend(new SubTitle($this->translate('Network'), 'sitemap'));
        parent::__construct($vm->getConnection());
    }

    public function renderRow($row)
    {
        // $this->add($this::row([
        //     new GrafanaVmPanel($this->vm->object(), [1, 3], $row->label, 'All')
        // ]));
        if ($this->withPerfImages) {
            return $this::row([
                $this->formatMultiLine($row),
                $this->prepareRowImages($row),
            ]);
        } else {
            return $this::row([$this->formatSimple($row)]);
        }
    }

    protected function linkToPortGroup($row)
    {
        if ($row->port_key === null) {
            return '-'; // TODO: explain
        } elseif ($row->portgroup_uuid === null) {
            return \sprintf($this->translate('Port %s'), $row->port_key);
        } else {
            return Html::sprintf(
                'Port %s on %s',
                $row->port_key,
                $row->portgroup_name
                /* // TODO:
                Link::create(
                    $row->portgroup_name,
                    'vspheredb/portgroup',
                    ['uuid' => \bin2hex($row->portgroup_uuid)],
                    ['data-base-target' => '_next']
                )
                */
            );
        }
    }

    protected function formatSimple($row)
    {
        return Html::sprintf(
            '%s (%s), %s',
            Html::tag('strong', $row->label),
            $row->mac_address,
            $this->linkToPortGroup($row)
        );
    }

    protected function formatMultiLine($row)
    {
        return [
            Html::tag('strong', $row->label),
            Html::tag('br'),
            $this->translate('MAC Address') . ': ' . $row->mac_address,
            Html::tag('br'),
            $this->linkToPortGroup($row)
        ];
    }

    protected function prepareRowImages($row)
    {
        return [
            RrdImg::vmIfTraffic($this->moref, $row->hardware_key),
            RrdImg::vmIfPackets($this->moref, $row->hardware_key),
        ];
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['vna' => 'vm_network_adapter'],
            [
                'vh.label',
                'vna.hardware_key',
                'vna.port_key',
                'vna.mac_address',
                'vna.address_type',
                'vna.portgroup_uuid',
                'portgroup_name' => 'pgo.object_name',
            ]
        )->join(
            ['vh' => 'vm_hardware'],
            'vh.vm_uuid = vna.vm_uuid AND vh.hardware_key = vna.hardware_key',
            []
        )->joinLeft(
            ['pgo' => 'object'],
            'pgo.uuid = vna.portgroup_uuid',
            []
        )->where('vna.vm_uuid = ?', $this->vm->get('uuid'))->order('vh.label ASC');

        return $query;
    }
}
