<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class PerfInterval
{
    /** @var bool */
    public $enabled;

    /** @var int 1, 2, 3, 4... */
    public $key;

    /** @var int 86400, 604800, 2592000, 31536000... */
    public $length;

    /** @var int 1, ... */
    public $level;

    /** @var string 'Past Day', 'Past Week', 'Past Month', 'Past Year'... */
    public $name;

    /** @var int 300, 1800, 7200, 86400... */
    public $samplingPeriod;
}
