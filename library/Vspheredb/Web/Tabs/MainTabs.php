<?php

namespace Icinga\Module\Vspheredb\Web\Tabs;

use dipl\Translation\TranslationHelper;
use dipl\Web\Widget\Tabs;
use Exception;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\Migrations;

class MainTabs extends Tabs
{
    use TranslationHelper;

    /** @var Db|null  */
    protected $connection;

    public function __construct(Db $connection = null)
    {
        $this->connection = $connection;
        // We are not a BaseElement, not yet
        $this->assemble();
    }

    protected function assemble()
    {
        if ($this->connection) {
            $connection = $this->connection;
        } else {
            try {
                $connection = Db::newConfiguredInstance();
            } catch (Exception $e) {
                $connection = null;
            }
        }

        if ($connection) {
            $migrations = new Migrations($connection);

            if ($migrations->hasSchema()) {
                $this->add('vcenters', [
                    'label'     => $this->translate('vCenters'),
                    'url'       => 'vspheredb/vcenters',
                ])->add('servers', [
                    'label'     => $this->translate('Servers'),
                    'url'       => 'vspheredb/vcenter/servers',
                ]);
            }
        }

        $this->add('configuration', [
            'label' => $this->translate('Configuration'),
            'url'   => 'vspheredb/configuration',
        ]);
        if ($migrations->hasSchema()) {
            $this->add('daemon', [
                'label' => $this->translate('Daemon'),
                'url' => 'vspheredb/daemon',
            ]);
        }
    }
}
