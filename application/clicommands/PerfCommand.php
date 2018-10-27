<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Module\Vspheredb\CheckPluginHelper;
use Icinga\Module\Vspheredb\Db;

class PerfCommand extends CommandBase
{
    use CheckPluginHelper;

    public function hostAction()
    {
        $sql = 'SELECT o.object_name, vdu.disk_path, vdu.capacity,'
          . ' vdu.free_space from object o join vm_disk_usage vdu'
          . ' on vdu.vm_uuid = o.uuid';
        $db = Db::newConfiguredInstance()->getDbAdapter();
        foreach ($db->fetchAll($sql) as $row) {
            $ciName = str_replace(' ', '_', $row->object_name);
            $path = str_replace('/', '_', $row->disk_path);
            $path = str_replace(' ', '_', $path);
            $ci = $ciName . ':' . $path;
            $lineFormat = $ci
                . ' free_space=' . $row->free_space
                . ' capacity=' . $row->capacity
                . ' '
                . time()
                . "\n";
            echo $lineFormat;
        }
    }
}
