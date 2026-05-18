<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use AllowDynamicProperties;

#[AllowDynamicProperties]
class RetrievePropertiesResponse
{
    /** @var ObjectContent[] */
    public $returnval = [];
}
