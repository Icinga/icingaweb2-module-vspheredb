<?php

namespace Icinga\Module\Vspheredb\MappedClass;

use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;

// https://www.vmware.com/support/developer/converter-sdk/conv61_apireference/vmodl.query.PropertyCollector.ObjectContent.html
class ObjectContent
{
    /** @var MissingProperty[] */
    public $missingSet;

    /** @var ManagedObjectReference */
    public $obj;

    /** @var DynamicProperty[]|null */
    public $propSet;

    public function hasMissingProperties()
    {
        return $this->missingSet !== null;
    }

    public function reportsNotAuthenticated()
    {
        if ($this->missingSet === null) {
            return false;
        }

        foreach ($this->missingSet as $missingProperty) {
            if ($missingProperty->isNotAuthenticated()) {
                return true;
            }
        }

        return false;
    }

    public function toNewObject()
    {
        return ApiClassMap::createInstanceForObjectContent($this);
    }
}
