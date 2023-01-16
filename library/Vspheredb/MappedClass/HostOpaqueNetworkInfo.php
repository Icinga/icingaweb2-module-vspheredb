<?php

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
class HostOpaqueNetworkInfo
{
    /**
     * The ID of the opaque network
     *
     * @var string
     */
    public $opaqueNetworkId;

    /**
     * The name of the opaque network
     *
     * @var string
     */
    public $opaqueNetworkName;

    /**
     * The type of the opaque network
     *
     * @var string
     */
    public $opaqueNetworkType;

    /**
     * IDs of networking zones that back the opaque network
     *
     * Since vSphere API 6.0
     *
     * @var string[]
     */
    public $pnicZone;
}
