<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Data\SimpleQueryPaginationAdapter;
use gipfl\IcingaWeb2\Table\QueryBasedTable;
use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Data\SimpleQuery;
use ipl\Html\HtmlElement;
use stdClass;

class ArrayTable extends QueryBasedTable
{
    /** @var stdClass */
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
        $this->getAttributes()->set('data-base-target', '_self');
    }

    public function renderRow($row): HtmlElement
    {
        return $this::row((array) $row);
    }

    protected function getPaginationAdapter(): SimpleQueryPaginationAdapter
    {
        return new SimpleQueryPaginationAdapter($this->getQuery());
    }

    public function getQuery(): SimpleQuery
    {
        return $this->prepareQuery();
    }

    protected function fetchQueryRows(): array
    {
        return $this->getQuery()->fetchAll();
    }

    protected function prepareQuery(): SimpleQuery
    {
        return (new ArrayDatasource(array_values((array) $this->rows)))->select();
    }
}
