<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\DbObject\HostSystem;

class HostPciDevicesTable extends ZfQueryBasedTable
{
    protected $defaultAttributes = [
        'class' => 'common-table preformatted',
        'data-base-target' => '_next',
    ];

    protected $searchColumns = [
        'id',
        'vendor_name',
        'device_name'
    ];

    /** @var HostSystem */
    protected $host;

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('ID'),
            $this->translate('Device (Vendor)'),
        ];
    }

    public function renderRow($row)
    {
        return static::row([
            $row->id,
            sprintf('%s (%s)', $row->device_name, $row->vendor_name),
        ]);
    }

    public function filterHost(HostSystem $host)
    {
        $this->host = $host;

        return $this;
    }

    protected function prepareQuery()
    {
        $query = $this->db()->select()->from([
            'hpd' => 'host_pci_device'
        ])->order('id ASC')->limit(1000);

        if ($this->host) {
            $query->where('host_uuid = ?', $this->host->get('uuid'));
        }

        return $query;
    }
}
