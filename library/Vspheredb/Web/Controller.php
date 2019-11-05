<?php

namespace Icinga\Module\Vspheredb\Web;

use Exception;
use gipfl\IcingaWeb2\CompatController;
use Icinga\Module\Vspheredb\Db;
use ipl\Html\Html;

class Controller extends CompatController
{
    /** @var Db */
    private $db;

    protected function db()
    {
        if ($this->db === null) {
            try {
                $this->db = Db::newConfiguredInstance();
                $migrations = new Db\Migrations($this->db);
                if (! $migrations->hasSchema()) {
                    $this->redirectToConfiguration();
                }
            } catch (Exception $e) {
                $this->redirectToConfiguration();
            }
        }

        return $this->db;
    }

    /**
     * @param $title
     * @param null $icon
     */
    protected function addSubTitle($title, $icon = null)
    {
        $title = Html::tag('h2', null, $title);

        if ($icon !== null) {
            $title->addAttributes(['class' => "icon-$icon"]);
        }

        $this->content()->add($title);
    }

    protected function redirectToConfiguration()
    {
        if ($this->getRequest()->getControllerName() !== 'configuration') {
            $this->redirectNow('vspheredb/configuration');
        }
    }
}
