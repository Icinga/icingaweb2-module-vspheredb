<?php

namespace Icinga\Module\Vspheredb\Web\Tabs;

use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\Tabs;
use Exception;
use Icinga\Module\Vspheredb\Db;

class ConfigTabs extends Tabs
{
    use TranslationHelper;

    /** @var Db|null  */
    protected $connection;

    public function __construct(?Db $connection = null)
    {
        $this->connection = $connection;
        // We are not a BaseElement, not yet
        $this->assemble();
    }

    protected function assemble()
    {
        if ($this->connection) {
            $migrations = Db::migrationsForDb($this->connection);
        } else {
            try {
                $migrations = Db::migrationsForDb(Db::newConfiguredInstance());
            } catch (Exception $e) {
                $migrations = null;
            }
        }

        if ($migrations && $migrations->hasSchema()) {
            $this->add('servers', [
                'label' => $this->translate('Servers'),
                'url' => 'vspheredb/configuration/servers',
            ]);
            $this->add('perfdata', [
                'label' => $this->translate('Performance Data'),
                'url'   => 'vspheredb/perfdata/consumers',
            ]);

            // Disable Tab unless #160 is ready
            $this->add('monitoring', [
                'label' => $this->translate('Monitoring'),
                'url' => 'vspheredb/configuration/monitoring',
            ]);
        }

        $this->add('database', [
            'label' => $this->translate('Database'),
            'url'   => 'vspheredb/configuration/database',
        ]);
    }
}
