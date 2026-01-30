<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\CustomValues;
use Icinga\Module\Vspheredb\Web\Widget\Addon\NetBackupRunDetails;

class NetBackup extends SimpleBackupTool
{
    public const PREFIX = 'Veritas NetBackup: ';

    // NB is Veritas NetBackup
    public const CV_LAST_BACKUP = 'NB_LAST_BACKUP';

    public const CV_EXCLUDE = 'NB_EXCLUDE_FROM_BACKUP';

    /** @var string[] */
    protected array $customValues = [
        self::CV_LAST_BACKUP,
        self::CV_EXCLUDE
    ];

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Veritas NetBackup';
    }

    /**
     * @return NetBackupRunDetails
     */
    public function getInfoRenderer(): NetBackupRunDetails
    {
        return new NetBackupRunDetails($this);
    }

    /**
     * @param CustomValues $values
     *
     * @return void
     */
    protected function parseCustomValues(CustomValues $values): void
    {
        if ($values->has(self::CV_LAST_BACKUP)) {
            $this->parseLastBackup($values->get(self::CV_LAST_BACKUP));
        }
        if ($values->has(self::CV_EXCLUDE)) {
            $this->lastAttributes['Excluded'] = $values->get(self::CV_EXCLUDE);
        }
    }

    /**
     * @param string $string
     *
     * @return void
     */
    protected function parseLastBackup(string $string): void
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
