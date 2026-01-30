<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use Closure;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;

abstract class TableColumn
{
    /** @var ?string */
    private ?string $alias = null;

    /** @var array|string|null */
    private array|string|null $column = null;

    /** @var ?string */
    private ?string $title = null;

    /** @var ?Closure */
    private ?Closure $renderer = null;

    /** @var array|string|null */
    private array|string|null $sortExpression = null;

    /** @var string */
    private string $defaultSortDirection = 'ASC';

    public function getRequiredDbColumns(): array
    {
        $column = $this->getColumn();
        if (is_array($column)) {
            return $column;
        } else {
            return [$this->getAlias() => $column];
        }
    }

    public function getMainColumnExpression(): array|string|null
    {
        $column = $this->getColumn();
        if (is_array($column)) {
            return array_shift($column);
        } else {
            return $column;
        }
    }

    public function setRenderer(callable $callback): static
    {
        $this->renderer = $callback(...);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias(string $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return array|string|null
     */
    public function getColumn(): array|string|null
    {
        return $this->column;
    }

    /**
     * @param array|string $column
     *
     * @return TableColumn
     */
    public function setColumn(array|string $column): static
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $row
     *
     * @return ValidHtml|HtmlDocument|mixed
     */
    public function renderRow($row): mixed
    {
        if ($this->renderer === null) {
            return Html::wantHtml($row->{$this->getAlias()});
        } else {
            $func = $this->renderer;

            return $func($row);
        }
    }

    /**
     * @return array|string|null
     */
    public function getSortExpression(): array|string|null
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
     * @param array|string|null $sortExpression
     *
     * @return $this
     */
    public function setSortExpression(array|string|null $sortExpression): static
    {
        $this->sortExpression = $sortExpression;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultSortDirection(): string
    {
        return $this->defaultSortDirection;
    }

    /**
     * @param string $defaultSortDirection
     *
     * @return $this
     */
    public function setDefaultSortDirection(string $defaultSortDirection): static
    {
        $this->defaultSortDirection = $defaultSortDirection;
        return $this;
    }
}
