<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Format;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;

class HostPhysicalNicTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => 'common-table',
        'data-base-target' => '_next',
    ];

    /** @var HostSystem */
    protected $host;

    /** @var string */
    protected $moref;

    public function __construct(HostSystem $host)
    {
        $this->host = $host;
        $this->moref = $this->host->object()->get('moref');
        parent::__construct($host->getConnection());

        $this->prepend(new SubTitle(\sprintf(
            $this->translate('Network Interfaces (%s)'),
            // Hint: we could also count given NICs, but this helps to spot
            // eventual inconsistencies
            $host->get('hardware_num_nic')
        ), 'sitemap'));
    }

    public function renderRow($row)
    {
        $attributes = [];
        if ($row->link_speed_mb === null) {
            $attributes['class'] = 'disabled';
        }
        return $this::row([$this->formatSimple($row)], $attributes);
    }

    protected function formatSimple($row)
    {
        if ($row->link_speed_mb === null) {
            $speedInfo = $this->translate('Link is down');
        } else {
            $speedInfo = \sprintf(
                '%s %s',
                Format::linkSpeedMb($row->link_speed_mb),
                $row->link_duplex === 'y'
                    ? $this->translate('full duplex')
                    : $this->translate('half duplex')
            );
        }
        return Html::sprintf(
            '%s (%s: %s), %s%s',
            Html::tag('strong', $row->device),
            $this->translate('driver'),
            $row->driver,
            isset($row->mac_address) ? $row->mac_address . ', ' : '',
            $speedInfo
        );
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['hpn' => 'host_physical_nic'],
            [
                'hpn.nic_key',
                'hpn.auto_negotiate_supported',
                'hpn.device',
                'hpn.driver',
                'hpn.link_speed_mb',
                'hpn.link_duplex',
                'hpn.mac_address',
                'hpn.pci',
            ]
        )->where('hpn.host_uuid = ?', $this->host->get('uuid'))->order('hpn.device ASC');

        return $query;
    }
}
