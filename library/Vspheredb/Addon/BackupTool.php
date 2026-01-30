<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use ipl\Html\HtmlDocument;

/**
 * Interface BackupTool
 *
 * Warning: this interface is still subject to change
 *
 * @internal
 *
 * @package Icinga\Module\Vspheredb\Addon
 */
interface BackupTool
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param VirtualMachine $vm
     *
     * @return bool
     */
    public function wants(VirtualMachine $vm): bool;

    /**
     * @param VirtualMachine $vm
     *
     * @return void
     */
    public function handle(VirtualMachine $vm): void;

    /**
     * @return HtmlDocument|null
     */
    public function getInfoRenderer(): ?HtmlDocument;
}
