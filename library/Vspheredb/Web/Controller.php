<?php

namespace Icinga\Module\Vspheredb\Web;

use Icinga\Application\Config;
use Icinga\Module\Vspheredb\Db;
use dipl\Web\CompatController;

class Controller extends CompatController
{
    /** @var Db */
    private $db;

    protected function db()
    {
        if ($this->db === null) {
            $this->db = Db::fromResourceName(
                Config::module('vsphere')->get('db', 'resource')
            );
        }

        return $this->db;
    }
}
