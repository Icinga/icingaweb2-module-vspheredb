<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\CustomValues;
use Icinga\Module\Vspheredb\Web\Widget\Addon\VeeamBackupRunDetails;

class VeeamBackup extends SimpleBackupTool
{
    const PREFIX = 'Veeam Backup: ';

    const CV_LAST_BACKUP = 'NB_LAST_BACKUP';

    const CV_EXCLUDE = 'NB_EXCLUDE_FROM_BACKUP';

    protected $customValues = [
        self::CV_LAST_BACKUP,
        self::CV_EXCLUDE
    ];

    public function getName()
    {
        return 'Veeam Backup & Replication';
    }

    /**
     * @return VeeamBackupRunDetails
     */
    public function getInfoRenderer()
    {
        return new VeeamBackupRunDetails($this);
    }

    protected function parseCustomValues(CustomValues $values)
    {
        if ($values->has(self::CV_LAST_BACKUP)) {
            $this->parseLastBackup($values->get(self::CV_LAST_BACKUP));
        }
        if ($values->has(self::CV_EXCLUDE)) {
            $this->lastAttributes['Excluded'] = $values->get(self::CV_EXCLUDE);
        }
    }

    protected function parseLastBackup($string)
    {
        // Sun Sep 13 00:27:42 2020 +0200,backuphost.name,jobname
        $parts = \explode(',', $string);
        $attributes = [];
        if (count($parts) === 3) {
            $attributes['Time'] = strtotime($parts[0]);
            $attributes['Backup host'] = $parts[1];
            $attributes['Job name'] = $parts[2];
        }
        $this->lastAttributes = $attributes;
    }
}
