<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Addon;

use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Addon\VeeamBackup;

class VeeamBackupRunDetails extends NameValueTable
{
    use TranslationHelper;

    /**
     * VeeamBackupRunDetails constructor.
     * @param VeeamBackup $details
     */
    public function __construct(VeeamBackup $details)
    {
        $attributes = $details->requireParsedAttributes();

        $this->addNameValuePairs([
            $this->translate('Job name')      => $attributes['Job name'],
            $this->translate('Last Run Time') => DateFormatter::formatDateTime($attributes['Time']),
            $this->translate('Backup host')   => $attributes['Backup host'],
            $this->translate('Backup folder') => $attributes['Backup folder'],
        ]);
    }
}
