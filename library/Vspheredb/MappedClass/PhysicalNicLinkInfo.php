<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class PhysicalNicLinkInfo
{
    /**
     * Flag to indicate whether or not the link is capable of full-duplex ("true")
     * or only half-duplex ("false").
     *
     * @var boolean
     */
    public $duplex;

    /**
     * Bit rate on the link
     *
     * @var int
     */
    public $speedMb;
}
