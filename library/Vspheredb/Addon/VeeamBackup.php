<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\Addon\VeeamBackupRunDetails;
use RuntimeException;

class VeeamBackup extends SimpleBackupTool
{
    public const PREFIX = 'Veeam Backup: ';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Veeam Backup & Replication';
    }

    /**
     * @param VirtualMachine $vm
     *
     * @return bool
     */
    public function wants(VirtualMachine $vm): bool
    {
        return $this->wantsAnnotation($vm->get('annotation'));
    }

    /**
     * @param VirtualMachine $vm
     *
     * @return void
     */
    public function handle(VirtualMachine $vm): void
    {
        $this->parseAnnotation($vm->get('annotation'));
    }

    /**
     * @return VeeamBackupRunDetails
     */
    public function getInfoRenderer(): VeeamBackupRunDetails
    {
        return new VeeamBackupRunDetails($this);
    }

    /**
     * @return array
     */
    public function requireParsedAttributes(): array
    {
        $attributes = $this->getAttributes();
        if ($attributes === null) {
            throw new RuntimeException('Got no Veeam Backup annotation info');
        }

        return $attributes;
    }
}
