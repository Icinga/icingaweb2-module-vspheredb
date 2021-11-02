<?php

namespace Icinga\Module\Vspheredb\Web\Table;

class VsphereApiConnectionTable extends ArrayTable
{
    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('VCenter'),
            $this->translate('Server'),
            $this->translate('State'),
        ];
    }
}
