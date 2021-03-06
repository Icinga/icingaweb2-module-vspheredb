<?php

namespace Icinga\Module\Vspheredb\Web;

use Exception;
use gipfl\IcingaWeb2\CompatController;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use ipl\Html\Html;

class Controller extends CompatController
{
    /** @var Db */
    private $db;

    public function init()
    {
        parent::init();
        if ($this->view->compact) {
            $this->controls()->addAttributes([
                'class' => 'show-compact'
            ]);
        }
    }

    protected function addHint($message, $class = 'information')
    {
        $this->content()->add(Html::tag('p', [
            'class' => $class
        ], $message));

        return $this;
    }

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

    protected function requireVCenter($paramName = 'vcenter')
    {
        $hexUuid = $this->params->getRequired($paramName);
        return VCenter::loadWithHexUuid($hexUuid, $this->db());
    }

    protected function redirectToConfiguration()
    {
        if ($this->getRequest()->getControllerName() !== 'configuration') {
            $this->redirectNow('vspheredb/configuration');
        }
    }
}
