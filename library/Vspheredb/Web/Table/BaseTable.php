<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\IcingaWeb2\Url;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

abstract class BaseTable extends ZfQueryBasedTable
{
    /** @var TableColumn[] */
    private $availableColumns = [];

    /** @var TableColumn[] */
    private $chosenColumns;

    private $isInitialized = false;

    /** @var Url */
    private $sortUrl;

    /** @var string */
    private $sortParam;

    /** @var array */
    private $sortColums = [];

    public function chooseColumns(array $columnNames)
    {
        $this->assertInitialized();
        if ($columnNames === ['___ALL___']) {
            $this->chosenColumns = $this->getAvailableColumns();

            return $this;
        }

        $this->chosenColumns = [];
        foreach ($this->getAvailableColumns() as $column) {
            $alias = $column->getAlias();
            if (in_array($alias, $columnNames)) {
                $this->chosenColumns[$alias] = $column;
            }
        }

        return $this;
    }

    public function getColumnsToBeRendered()
    {
        return $this->getChosenTitles();
    }

    /**
     * @return TableColumn[]
     */
    public function getAvailableColumns()
    {
        return $this->availableColumns;
    }

    public function hasColumn($name)
    {
        return array_key_exists($name, $this->availableColumns);
    }

    /**
     * @param string $alias
     * @return TableColumn
     */
    public function getAvailableColumn($alias)
    {
        if (array_key_exists($alias, $this->availableColumns)) {
            return $this->availableColumns[$alias];
        } else {
            throw new InvalidArgumentException(sprintf('No column named "%s" is available', $alias));
        }
    }

    public function assertInitialized()
    {
        if (! $this->isInitialized) {
            $this->isInitialized = true;
            $this->initialize();
        }
    }

    public function nextHeader()
    {
        return parent::nextHeader()->setAttributes([
            'data-base-target' => '_self'
        ]);
    }

    protected function addHeaderColumnsTo(HtmlElement $parent)
    {
        if ($this->sortUrl) {
            $this->addSortHeadersTo($parent);
        } else {
            parent::addHeaderColumnsTo($parent);
        }

        return $parent;
    }

    protected function initialize()
    {
    }

    protected function getChosenColumns()
    {
        $this->assertInitialized();
        // TODO: I do not want to call this:
        $this->getChosenColumnNames();

        return $this->chosenColumns;
    }

    public function getDefaultColumnNames()
    {
        return array_keys($this->getAvailableColumns());
    }

    public function getChosenColumnNames()
    {
        $this->assertInitialized();
        if ($this->chosenColumns === null) {
            $this->chooseColumns($this->getDefaultColumnNames());
        }

        return array_keys($this->chosenColumns);
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

    protected function createColumn($alias, $title = null, $column = null)
    {
        return new SimpleColumn($alias, $title, $column);
    }

    /**
     * @param Url $url
     * @param string $sortParam
     * @return $this
     */
    public function handleSortUrl(Url $url, $sortParam = 'sort')
    {
        $this->sortParam = $sortParam;
        $this->sortUrl = $url;
        $sort = $url->getParam($sortParam);
        if (null !== $sort) {
            $this->sortBy($sort);
        }

        return $this;
    }

    /**
     * @param string|array $columns
     * @return $this
     */
    public function sortBy($columns)
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $query = $this->getQuery();
        foreach ($columns as $columnName) {
            $space = strpos($columnName, ' ');
            if (false === $space) {
                $sortColumn = $this->getAvailableColumn($columnName);
                $direction = $sortColumn->getDefaultSortDirection();
            } else {
                $direction = substr($columnName, $space + 1);
                $columnName = substr($columnName, 0, $space);
                $sortColumn = $this->getAvailableColumn($columnName);
            }
            $this->sortColums[$columnName] = $direction;
            $sort = $sortColumn->getSortExpression();
            if (is_array($sort)) {
                foreach ($sort as $s) {
                    $query->order("$s $direction");
                }
            } else {
                $query->order("$sort $direction");
            }
        }

        return $this;
    }

    protected function addSortIcon(TableColumn $column, BaseHtmlElement $element)
    {
        $icons = [
            'ASC'  => 'up-dir',
            'DESC' => 'down-dir',
        ];
        if (array_key_exists($column->getAlias(), $this->sortColums)) {
            $element->add(Icon::create($icons[$this->sortColums[$column->getAlias()]]));
        }

        return $element;
    }

    protected function getNextSortLinkString(TableColumn $column)
    {
        $string = $column->getAlias();
        if (array_key_exists($column->getAlias(), $this->sortColums)) {
            $current = $this->sortColums[$column->getAlias()];
            if ($current === $column->getDefaultSortDirection()) {
                $string .= ' ' . ($current === 'ASC' ? 'DESC' : 'ASC');
            }

            return $string;
        }

        if ($column->getDefaultSortDirection() === 'ASC') {
            return $string;
        } else {
            return "$string DESC";
        }
    }

    /**
     * TODO: we should consider introducing TablePlugins for similar tasks
     *
     * @param HtmlElement $parent
     * @return HtmlElement
     */
    protected function addSortHeadersTo(HtmlElement $parent)
    {
        // Hint: MUST be set
        $url = $this->sortUrl;

        foreach ($this->getChosenColumns() as $column) {
            $parent->add(
                Html::tag('th')->setContent($this->addSortIcon(
                    $column,
                    Link::create(
                        $column->getTitle(),
                        $url->with($this->sortParam, $this->getNextSortLinkString($column))
                    )
                ))
            );
        }

        return $parent;
    }
}
