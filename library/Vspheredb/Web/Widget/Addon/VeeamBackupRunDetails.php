<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Web\Widget\Addon;

use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Addon\VeeamBackup;
use ipl\I18n\Translation;

class VeeamBackupRunDetails extends NameValueTable
{
    use Translation;

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
