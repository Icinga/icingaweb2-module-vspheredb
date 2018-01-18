<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\PerformanceCounterTable;

class ConfigurationController extends Controller
{
    public function countersAction()
    {
        $this->addSingleTab('Counters');
        $this->addTitle('Performance Counters');
        (new PerformanceCounterTable($this->db()))->renderTo($this);
    }
}
