<?php

namespace Icinga\Module\Vspheredb\Controllers;

use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\VmsWithDuplicateProperty;
use ipl\Html\Html;

class AnomaliesController extends Controller
{
    // TODO: Overbooked datastores
    public function indexAction()
    {
        $this->addSingleTab($this->translate('Anomalies'));
        $this->addTable('bios_uuid', $this->translate('Bios UUID'));
        $this->addTable('instance_uuid', $this->translate('Instance UUID'));
        $this->addTable('guest_host_name', $this->translate('Guest host name'));
        $this->addTable('guest_ip_address', $this->translate('Guest IP address'));
    }

    protected function addTable($property, $title)
    {
        $table = VmsWithDuplicateProperty::create($this->db(), $property, $title);

        $count = count($table);
        if ($count) {
            $this->content()->add([
                Html::tag(
                    'h1',
                    null,
                    sprintf(
                        '%d Virtual Machines with duplicate %s',
                        $count,
                        $title
                    )
                ),
                $table
            ]);
        } else {
            $this->content()->add(
                Html::tag(
                    'h1',
                    null,
                    sprintf(
                        'There are no Virtual Machines with duplicate %s',
                        $title
                    )
                )
            );
        }
    }
}
