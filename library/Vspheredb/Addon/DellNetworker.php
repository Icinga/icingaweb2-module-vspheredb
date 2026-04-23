<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\CustomValues;

class DellNetworker extends SimpleBackupTool
{
    public const CV_LAST_BACKUP = 'Last EMC vProxy Backup';

    protected $customValues = [
        self::CV_LAST_BACKUP,
    ];

    public function getName()
    {
        return 'Dell Networker (EMC vProxy)';
    }

    protected function parseCustomValues(CustomValues $values)
    {
        if (!$values->has(self::CV_LAST_BACKUP)) {
            return;
        }

        $string = $values->get(self::CV_LAST_BACKUP);
        $attributes = [];

        // Format: Backup Server=backup01.local, Policy=RZ, Workflow=vm_day_backup, ...
        foreach (preg_split('/,\s*/', $string) as $part) {
            if (preg_match('/^([^=]+)=(.*)$/', trim($part), $m)) {
                $key = trim($m[1]);
                $value = trim($m[2]);

                if (in_array($key, ['StartTime', 'EndTime'])) {
                    $attributes[$key] = strtotime($value);
                } else {
                    $attributes[$key] = $value;
                }
            }
        }

        if (isset($attributes['EndTime'])) {
            $attributes['Time'] = $attributes['EndTime'];
        }

        $this->lastAttributes = $attributes;
    }
}
