<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Url;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;

class ToggleTableColumns extends ToggleFlagList
{
    /** @var BaseTable */
    protected BaseTable $table;

    protected string $iconMain = 'th-list';

    protected string $iconModified = 'th-list';

    public function __construct(BaseTable $table, Url $url)
    {
        parent::__construct($url, 'columns');
        $this->table = $table;
    }

    protected function getListLabel(): string
    {
        return '';
        // return $this->translate('Columns');
    }

    protected function getDefaultSelection(): array
    {
        return $this->table->getChosenColumnNames();
    }

    protected function setEnabled(array $enabled, array $all): void
    {
        $this->table->chooseColumns($enabled);
    }

    protected function getOptions(): array
    {
        $options = [];
        foreach ($this->table->getAvailableColumns() as $column) {
            $title = $column->getTitle();
            $alias = $column->getAlias();
            $options[$alias] = $title;
        }

        return $options;
    }
}
