<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller\ObjectsController;
use Icinga\Module\Vspheredb\Web\OverviewTree;

class OverviewController extends ObjectsController
{
    public function indexAction()
    {
        $type = $this->params->getRequired('type');
        $this->activateTab($type)
             ->addTitle('vSphere Overview')
             ->content()->add(new OverviewTree($this->db(), $type));
    }

    protected function activateTab($name)
    {
        $this->controls()->getTabs()->add('datastore', [
            'label' => $this->translate('Datastores'),
            'url'   => 'vspheredb/overview?type=datastore'
        ])->add('host', [
            'label' => $this->translate('Hosts'),
            'url'   => 'vspheredb/overview?type=host'
        ])->add('vm', [
            'label' => $this->translate('VMs'),
            'url'   => 'vspheredb/overview?type=vm'
        ])->activate($name);

        return $this;
    }
}
