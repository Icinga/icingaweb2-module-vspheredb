<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Addon;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Table\NameValueTable;
use Icinga\Date\DateFormatter;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\Addon\NetBackup;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\CheckRelatedLookup;

class NetBackupRunDetails extends NameValueTable
{
    use TranslationHelper;

    /**
     * @param NetBackup $details
     */
    public function __construct(NetBackup $details)
    {
        $attributes = $details->requireParsedAttributes();

        if (isset($attributes['Excluded']) && $attributes['Excluded'] === 'true') {
            $this->addNameValueRow(
                $this->translate('Excluded'),
                $this->translate('This VM has been excluded from Backup')
            );
            return;
        }

        $this->addNameValuePairs([
            $this->translate('Job name')      => $attributes['Job name'],
            $this->translate('Last Run Time') => DateFormatter::formatDateTime($attributes['Time']),
            $this->translate('Backup host')   => $this->renderBackupHost($attributes['Backup host']),
        ]);

        if (isset($attributes['Backup folder'])) {
            $this->addNameValueRow(
                $this->translate('Backup folder'),
                $attributes['Backup folder']
            );
        }
    }

    protected function renderBackupHost($name)
    {
        try {
            // TODO: this is ugly.
            $lookup = new CheckRelatedLookup(Db::newConfiguredInstance());
            $vm = $lookup->findOneBy('VirtualMachine', [
                'guest_host_name' => $name
            ]);
            return Link::create($name, 'vspheredb/vm', [
                'uuid' => \bin2hex($vm->get('uuid'))
            ]);
        } catch (NotFoundError $e) {
            return $name;
        }
    }
}
