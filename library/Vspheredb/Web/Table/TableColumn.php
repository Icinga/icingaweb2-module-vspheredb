<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use ipl\Html\Html;

abstract class TableColumn
{
    /** @var string */
    private $alias;

    /** @var string */
    private $column;

    /** @var string */
    private $title;

    /** @var callable */
    private $renderer;

    /** @var string|null */
    private $sortExpression;

    /** @var string */
    private $defaultSortDirection = 'ASC';

    public function getRequiredDbColumns()
    {
        $column = $this->getColumn();
        if (is_array($column)) {
            return $column;
        } else {
            return [$this->getAlias() => $column];
        }
    }

    public function getMainColumnExpression()
    {
        $column = $this->getColumn();
        if (is_array($column)) {
            return array_shift($column);
        } else {
            return $column;
        }
    }

    public function setRenderer($callback)
    {
        $this->renderer = $callback;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @param string $column
     * @return TableColumn
     */
    public function setColumn($column)
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function renderRow($row)
    {
        if ($this->renderer === null) {
            return Html::wantHtml($row->{$this->getAlias()});
        } else {
            $func = $this->renderer;

            return $func($row);
        }
    }

    /**
     * @return null|string
     */
    public function getSortExpression()
    {
        if (null === $this->sortExpression) {
            $column = $this->getColumn();
            if (is_array($column)) {
                return current($column);
            } else {
                return $column;
            }
        } else {
            return $this->sortExpression;
        }
    }

    /**
     * @param null|string|array $sortExpression
     * @return $this
     */
    public function setSortExpression($sortExpression)
    {
        $this->sortExpression = $sortExpression;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultSortDirection()
    {
        return $this->defaultSortDirection;
    }

    /**
     * @param string $defaultSortDirection
     * @return $this
     */
    public function setDefaultSortDirection($defaultSortDirection)
    {
        $this->defaultSortDirection = $defaultSortDirection;
        return $this;
    }
}
