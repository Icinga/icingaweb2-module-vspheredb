<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use dipl\Html\Link;

class VmsInFolderTable extends VmsTable
{
    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Name'),
            $this->translate('CPUs'),
            $this->translate('Memory'),
        ];
    }

    public function renderRow($row)
    {
        $caption = Link::create(
            $row->object_name,
            'vspheredb/vm',
            ['uuid' => bin2hex($row->uuid)]
        );

        $tr = $this::row([
            $caption,
            $row->hardware_numcpu,
            $this->formatMb($row->hardware_memorymb * 1024 * 1024)
        ]);
        $tr->attributes()->add('class', [$row->runtime_power_state, $row->overall_status]);

        return $tr;
    }
}
