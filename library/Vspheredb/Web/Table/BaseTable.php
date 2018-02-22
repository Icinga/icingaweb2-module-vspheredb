<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Web\Table\ZfQueryBasedTable;
use Icinga\Exception\IcingaException;
use Icinga\Util\Format;

abstract class BaseTable extends ZfQueryBasedTable
{
    /** @var TableColumn[] */
    private $availableColumns = [];

    /** @var TableColumn[] */
    private $chosenColumns = [];

    private $isInitialized = false;

    public function chooseColumns(array $columnNames)
    {
        foreach ($columnNames as $alias) {
            $this->chosenColumns[$alias] = $this->getAvailableColumn($alias);
        }
    }

    public function getAvailableColumn($alias)
    {
        if (array_key_exists($alias, $this->availableColumns)) {
            return $this->availableColumns[$alias];
        } else {
            throw new IcingaException('No column named "%s" is available', $alias);
        }
    }

    public function assertInitialized()
    {
        if (! $this->isInitialized) {
            $this->initialize();
            $this->isInitialized = true;
        }
    }

    protected function initialize()
    {
    }

    protected function getChosenColumns()
    {
        $this->assertInitialized();

        return $this->chosenColumns;
    }

    protected function getChosenTitles()
    {
        $titles = [];

        foreach ($this->getChosenColumns() as $column) {
            $titles[] = $column->getTitle();
        }

        return $titles;
    }

    protected function getRequiredDbColumns()
    {
        $columns = [];

        foreach ($this->getChosenColumns() as $column) {
            foreach ($column->getRequiredDbColumns() as $alias => $dbExpression) {
                $columns[$alias] = $dbExpression;
            }
        }

        return $columns;
    }

    public function renderRow($row)
    {
        $tr = $this::tr();
        foreach ($this->getChosenColumns() as $column) {
            $tr->add($this::td($column->renderRow($row)));
        }

        return $tr;
    }

    /**
     * @return TableColumn[]
     */
    protected function getAvaliableColumns()
    {
        return $this->availableColumns;
    }

    public function addAvailableColumn(TableColumn $column)
    {
        $this->availableColumns[$column->getAlias()] = $column;

        return $this;
    }

    /**
     * @param TableColumn[] $columns
     * @return $this
     */
    public function addAvailableColumns($columns)
    {
        foreach ($columns as $column) {
            $this->addAvailableColumn($column);
        }

        return $this;
    }

    protected function formatMb($mb)
    {
        return Format::bytes($mb * 1024 * 1024);
    }
}
