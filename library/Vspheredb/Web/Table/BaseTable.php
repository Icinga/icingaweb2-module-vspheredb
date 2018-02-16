<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use dipl\Web\Table\ZfQueryBasedTable;
use Icinga\Util\Format;

abstract class BaseTable extends ZfQueryBasedTable
{
    protected function formatMb($mb)
    {
        return Format::bytes($mb * 1024 * 1024);
    }
}
