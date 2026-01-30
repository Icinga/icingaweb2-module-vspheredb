<?php

namespace Icinga\Module\Vspheredb\Web\Table;

class VsphereApiConnectionTable extends ArrayTable
{
    public function getColumnsToBeRendered(): array
    {
        return [
            $this->translate('VCenter'),
            $this->translate('Server'),
            $this->translate('State'),
        ];
    }
}
