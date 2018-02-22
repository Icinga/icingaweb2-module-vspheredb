<?php

namespace Icinga\Module\Vspheredb\Web\Table;

class SimpleColumn extends TableColumn
{
    public function __construct($alias, $title = null, $column = null)
    {
        $this->setAlias($alias);
        $this->setTitle($title ?: $alias);
        $this->setColumn($column ?: $alias);
    }
}
