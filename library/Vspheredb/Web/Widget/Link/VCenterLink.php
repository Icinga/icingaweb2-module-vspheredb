<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Link;

use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Ramsey\Uuid\Uuid;

class VCenterLink extends Link
{
    public function __construct(VCenter $vCenter)
    {
        parent::__construct(
            $vCenter->get('name'),
            'vspheredb/vcenter',
            ['vcenter' => Uuid::fromBytes($vCenter->getUuid())->toString()],
            ['data-base-target' => '_next']
        );
    }
}
