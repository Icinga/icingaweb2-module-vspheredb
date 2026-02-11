<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Icinga\Util\Format;

abstract class PercentObjectsTable extends ObjectsTable
{
    protected function formatBytesPercent(object $row, string $name): string
    {
        $bytes = $row->$name;
        $percent = $row->{"{$name}_percent"};

        return sprintf('%s (%s)', Format::bytes($bytes, Format::STANDARD_IEC), $this->formatPercent($percent));
    }

    protected function formatPercent(string $value): string
    {
        return sprintf('%0.2f%%', $value);
    }
}
