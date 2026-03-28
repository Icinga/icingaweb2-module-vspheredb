<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Addon;

use gipfl\Web\Table\NameValueTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Addon\VRangerBackup;
use ipl\I18n\Translation;

class VRangerBackupRunDetails extends NameValueTable
{
    use Translation;

    /**
     * @param VRangerBackup $details
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
