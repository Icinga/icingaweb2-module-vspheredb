<?php

namespace Icinga\Module\Vspheredb\Web;

use Icinga\Module\Vspheredb\Db;
use dipl\Web\CompatController;
use Icinga\Module\Vspheredb\PathLookup;

class Controller extends CompatController
{
    /** @var Db */
    private $db;

    protected function db()
    {
        if ($this->db === null) {
            $this->db = Db::newConfiguredInstance();
        }

        return $this->db;
    }
}
