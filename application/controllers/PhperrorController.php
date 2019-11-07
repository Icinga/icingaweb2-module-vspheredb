<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Application\Icinga;
use Icinga\Application\Modules\Manager;
use Icinga\Web\Controller;

class PhperrorController extends Controller
{
    public function errorAction()
    {
        $this->getTabs()->add('error', array(
            'label' => $this->translate('Error'),
            'url'   => $this->getRequest()->getUrl()
        ))->activate('error');
        $msg = $this->translate(
            "PHP version 5.6.x is required for vSphereDB, you're running %s."
        );
        $this->view->title = $this->translate('Unsatisfied dependencies');
        $this->view->message = sprintf($msg, PHP_VERSION);
    }

    public function dependenciesAction()
    {
        $dependencies = $this->view->dependencies = $this->Module()->getDependencies();
        $modules = $this->view->modules = Icinga::app()->getModuleManager();
        // Hint: we're duplicating some code here
        $satisfied = true;
        foreach ($dependencies as $module => $required) {
            /** @var Manager $this ->modules */
            if ($modules->hasEnabled($module)) {
                $installed = $modules->getModule($module, false)->getVersion();
                $installed = \ltrim($installed, 'v'); // v0.6.0 VS 0.6.0
                if (\preg_match('/^([<>=]+)\s*v?(\d+\.\d+\.\d+)$/', $required, $match)) {
                    $operator = $match[1];
                    $vRequired = $match[2];
                    if (\version_compare($installed, $vRequired, $operator)) {
                        continue;
                    }
                }
            }
            $satisfied = false;
        }

        if ($satisfied) {
            $this->redirectNow('vspheredb/vcenters');
        }

        $this->setAutorefreshInterval(15);
        $this->getTabs()->add('error', array(
            'label' => $this->translate('Error'),
            'url'   => $this->getRequest()->getUrl()
        ))->activate('error');
        $msg = $this->translate(
            "vSphereDB depends on the following modules, please install/upgrade as required"
        );
        $this->view->title = $this->translate('Unsatisfied dependencies');
        $this->view->message = sprintf($msg, PHP_VERSION);
    }
}
