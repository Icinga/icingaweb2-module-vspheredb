<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\CustomValues;

class CohesityBackup extends SimpleBackupTool
{
    public const CV_BACKUP_TIME = 'Last Cohesity Backup Attempt Time';
    public const CV_BACKUP_STATUS = 'Last Cohesity Backup Status';

    protected $customValues = [
        self::CV_BACKUP_TIME,
        self::CV_BACKUP_STATUS,
    ];

    public function getName()
    {
        return 'Cohesity Backup';
    }

    protected function parseCustomValues(CustomValues $values)
    {
        $attributes = [];

        if ($values->has(self::CV_BACKUP_TIME)) {
            $timeStr = $values->get(self::CV_BACKUP_TIME);
            // Format: 2026/01/30-15:10:26-GMT+0100
            $normalized = preg_replace(
                '/^(\d{4})\/(\d{2})\/(\d{2})-(\d{2}:\d{2}:\d{2})-GMT([+-]\d{4})$/',
                '$1-$2-$3 $4 $5',
                $timeStr
            );
            $attributes['Time'] = strtotime($normalized);
        }

        if ($values->has(self::CV_BACKUP_STATUS)) {
            $attributes['Status'] = $values->get(self::CV_BACKUP_STATUS);
        }

        $this->lastAttributes = $attributes;
    }
}
