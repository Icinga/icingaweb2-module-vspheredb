<?php

namespace Icinga\Module\Vspheredb\Web;

use dipl\Html\Html;
use dipl\Html\HtmlString;
use Icinga\Module\Vspheredb\Db;
use dipl\Web\CompatController;
use Icinga\Module\Vspheredb\PathLookup;

class Controller extends CompatController
{
    /** @var Db */
    private $db;

    /** @var PathLookup */
    protected $pathLookup;

    protected function runFailSafe($callback)
    {
        try {
            if (is_callable($callback)) {
                $callback();
            } elseif (is_string($callback)) {
                $this->$callback();
            }
        } catch (\Exception $e) {
            $this->content()->add(HtmlString::create(Html::renderError($e)));
        } catch (\Error $e) {
            $this->content()->add(HtmlString::create(Html::renderError($e)));
        }
    }

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
            $this->db = Db::newConfiguredInstance();
        }

        return $this->db;
    }
}
