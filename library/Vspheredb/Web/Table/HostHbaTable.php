<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\Format;
use Icinga\Module\Vspheredb\Web\Widget\SubTitle;
use ipl\Html\Html;

class HostHbaTable extends ZfQueryBasedTable
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
            $this->translate('HBA (%s)'),
            // Hint: we could also count given HBAs, but this helps to spot
            // eventual inconsistencies
            $host->get('hardware_num_hba')
        ), 'sitemap'));
    }

    public function renderRow($row)
    {
        $attributes = [];
        if ($row->status !== 'online') {
            $attributes['class'] = 'disabled';
        }

        return $this::row([$this->formatSimple($row)], $attributes);
    }

    protected function formatSimple($row)
    {
        return Html::sprintf(
            '%s (%s: %s), %s: %s',
            Html::tag('strong', $row->device),
            $this->translate('driver'),
            $row->driver,
            $row->model,
            $row->status
        );
    }

    public function prepareQuery()
    {
        $query = $this->db()->select()->from(
            ['hh' => 'host_hba'],
            [
                'hh.hba_key',
                'hh.device',
                'hh.driver',
                'hh.status',
                'hh.model',
                'hh.pci',
            ]
        )->where('hh.host_uuid = ?', $this->host->get('uuid'))->order('hh.device ASC');

        return $query;
    }
}
