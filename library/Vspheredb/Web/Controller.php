<?php

namespace Icinga\Module\Vspheredb\Web;

use Icinga\Application\Config;
use Icinga\Module\Vspheredb\Db;
use dipl\Web\CompatController;
use Icinga\Module\Vspheredb\PathLookup;

class Controller extends CompatController
{
    /** @var Db */
    private $db;

    /** @var PathLookup */
    protected $pathLookup;

    protected function pathLookup()
    {
        if ($this->pathLookup === null) {
            $this->pathLookup = new PathLookup($this->db());
        }

        return $this->pathLookup;
    }

    protected function db()
    {
        if ($this->db === null) {
            $this->db = Db::fromResourceName(
                Config::module('vspheredb')->get('db', 'resource')
            );
        }

        return $this->db;
    }
}
