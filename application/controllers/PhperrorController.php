<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Application\Icinga;
use Icinga\Module\Vspheredb\Application\DependencyChecker;
use Icinga\Module\Vspheredb\Web\Table\Dependency\DependencyInfoTable;
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
        $checker = new DependencyChecker(Icinga::app());
        if ($checker->satisfiesDependencies($this->Module())) {
            $this->redirectNow('vspheredb/vcenters');
        }
        $this->setAutorefreshInterval(15);
        $this->getTabs()->add('error', [
            'label' => $this->translate('Error'),
            'url'   => $this->getRequest()->getUrl()
        ])->activate('error');
        $this->view->title = $this->translate('Unsatisfied dependencies');
        $this->view->table = (new DependencyInfoTable($checker, $this->Module()))->render();
        $this->view->message = $this->translate(
            "Icinga vSphereDb depends on the following modules, please install/upgrade as required"
        );
    }
}
