<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class CustomFieldValue
{
    /** @var int CustomField ID - references CustomFieldDefs in CustomFieldsManager */
    public $key;

    /** @var string */
    public $value;
}
