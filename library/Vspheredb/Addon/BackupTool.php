<?php

namespace Icinga\Module\Vspheredb\Addon;

use dipl\Html\HtmlDocument;
use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;

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
    public function getName();

    /**
     * @param VirtualMachine $vm
     * @return bool
     */
    public function wants(VirtualMachine $vm);

    /**
     * @param VirtualMachine $vm
     * @throws IcingaException
     */
    public function handle(VirtualMachine $vm);

    /**
     * @return HtmlDocument|null
     */
    public function getInfoRenderer();
}
