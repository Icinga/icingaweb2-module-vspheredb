<?php

namespace Icinga\Module\Vspheredb\Web;

use Exception;
use gipfl\IcingaWeb2\CompatController;
use Icinga\Module\Vspheredb\Auth\RestrictionHelper;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;

class Controller extends CompatController
{
    /** @var Db */
    private $db;

    /** @var ?RestrictionHelper */
    private $restrictionHelper;

    public function init()
    {
        parent::init();
        if ($this->view->compact) {
            $this->controls()->addAttributes([
                'class' => 'show-compact'
            ]);
        }
    }

    protected function db()
    {
        if ($this->db === null) {
            try {
                $this->db = Db::newConfiguredInstance();
                $migrations = Db::migrationsForDb($this->db);
                if (! $migrations->hasSchema()) {
                    $this->redirectToConfiguration();
                }
            } catch (Exception $e) {
                $this->redirectToConfiguration();
            }
        }

        return $this->db;
    }

    protected function getRestrictionHelper(): RestrictionHelper
    {
        if ($this->restrictionHelper === null) {
            $this->restrictionHelper = new RestrictionHelper($this->Auth(), $this->db());
        }

        return $this->restrictionHelper;
    }

    protected function requireVCenter($paramName = 'vcenter'): VCenter
    {
        $vCenter = VCenter::loadWithUuid($this->params->getRequired($paramName), $this->db());
        $this->getRestrictionHelper()->assertAccessToVCenterUuidIsGranted($vCenter->get('instance_uuid'));
        return $vCenter;
    }

    protected function redirectToConfiguration()
    {
        if ($this->getRequest()->getControllerName() !== 'configuration'
            || $this->getRequest()->getActionName() !== 'database'
        ) {
            $this->redirectNow('vspheredb/configuration/database');
        }
    }
}
