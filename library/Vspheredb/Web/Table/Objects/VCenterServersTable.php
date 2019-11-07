<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;
use Icinga\Module\Vspheredb\Web\Table\SimpleColumn;

class VCenterServersTable extends BaseTable
{
    protected function initialize()
    {
        $this->addAvailableColumns([
            (new SimpleColumn('server', $this->translate('Server'), [
                'scheme' => 'vcs.scheme',
                'username' => 'vcs.username',
                'host' => 'vcs.host',
                'id' => 'vcs.id',
            ]))->setRenderer(function ($row) {
                return Link::create(
                    $this->makeUrl($row),
                    'vspheredb/vcenter/server',
                    ['id' => $row->id]
                );
            })->setDefaultSortDirection('DESC'),
            (new SimpleColumn('vcenter', $this->translate('VCenter'), 'vc.name')),
        ]);
    }

    protected function makeUrl($row)
    {
        return sprintf(
            '%s://%s@%s',
            $row->scheme,
            $row->username,
            $row->host
        );
    }

    public function prepareQuery()
    {
        return $this->db()->select()->from(
            ['vcs' => 'vcenter_server'],
            $this->getRequiredDbColumns()
        )->joinLeft(
            ['vc' => 'vcenter'],
            // 'vc.instance_uuid = vcs.vcenter_uuid',
            'vc.id = vcs.vcenter_id',
            []
        );
    }
}
