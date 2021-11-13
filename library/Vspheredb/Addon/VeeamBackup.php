<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\Addon\VeeamBackupRunDetails;
use RuntimeException;

class VeeamBackup extends SimpleBackupTool
{
    const PREFIX = 'Veeam Backup: ';

    public function getName()
    {
        return 'Veeam Backup & Replication';
    }

    /**
     * @param VirtualMachine $vm
     * @return bool
     */
    public function wants(VirtualMachine $vm)
    {
        return $this->wantsAnnotation($vm->get('annotation'));
    }

    /**
     * @param VirtualMachine $vm
     */
    public function handle(VirtualMachine $vm)
    {
        $this->parseAnnotation($vm->get('annotation'));
    }

    /**
     * @return VeeamBackupRunDetails
     */
    public function getInfoRenderer()
    {
        return new VeeamBackupRunDetails($this);
    }

    /**
     * @return array
     */
    public function requireParsedAttributes()
    {
        $attributes = $this->getAttributes();
        if ($attributes === null) {
            throw new RuntimeException('Got no Veeam Backup annotation info');
        }

        return $attributes;
    }
}
