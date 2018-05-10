<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Addon;

use dipl\Translation\TranslationHelper;
use dipl\Web\Widget\NameValueTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Addon\VRangerBackup;

class VRangerBackupRunDetails extends NameValueTable
{
    use TranslationHelper;

    /**
     * @param VRangerBackup $details
     * @throws \Icinga\Exception\IcingaException
     */
    public function __construct(VRangerBackup $details)
    {
        $attributes = $details->requireParsedAttributes();

        $this->addNameValuePairs([
            $this->translate('Result')        => $attributes['Result'],
            $this->translate('Last Run Time') => DateFormatter::formatDateTime($attributes['Time']),
            $this->translate('Type')          => $attributes['Type'],
            $this->translate('Repository')    => $attributes['Repository'],
        ]);
    }
}
