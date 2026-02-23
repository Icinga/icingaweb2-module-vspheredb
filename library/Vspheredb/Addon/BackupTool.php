<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\CustomValues;
use ipl\Html\HtmlDocument;
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
     */
    public function handle(VirtualMachine $vm);

    /**
     * @return HtmlDocument|null
     */
    public function getInfoRenderer();
}
