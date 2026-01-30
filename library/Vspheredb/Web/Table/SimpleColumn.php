<?php

namespace Icinga\Module\Vspheredb\Web\Table;

class SimpleColumn extends TableColumn
{
    public function __construct(string $alias, ?string $title = null, string|array|null $column = null)
    {
        $this->setAlias($alias);
        $this->setTitle($title ?: $alias);
        $this->setColumn($column ?: $alias);
    }
}
