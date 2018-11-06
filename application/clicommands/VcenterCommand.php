<?php

namespace Icinga\Module\Vspheredb\Clicommands;

class VcenterCommand extends CommandBase
{
    /**
     * Deprecated
     */
    public function initializeAction()
    {
        $this->fail('Command has been deprecated, please check our documentation');
    }
}
