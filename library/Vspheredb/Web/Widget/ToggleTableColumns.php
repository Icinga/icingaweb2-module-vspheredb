<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Url;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;

class ToggleTableColumns extends ToggleFlagList
{
    /** @var BaseTable */
    protected $table;

    protected $iconMain = 'th-list';

    protected $iconModified = 'th-list';

    public function __construct(BaseTable $table, Url $url)
    {
        parent::__construct($url, 'columns');
        $this->table = $table;
    }

    protected function getListLabel()
    {
        return '';
        // return $this->translate('Columns');
    }

    protected function getDefaultSelection()
    {
        return $this->table->getChosenColumnNames();
    }

    protected function setEnabled($enabled, $all)
    {
        $this->table->chooseColumns($enabled);
    }

    protected function getOptions()
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
