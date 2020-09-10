<?php

namespace Icinga\Module\Vspheredb\Web\Widget;

use gipfl\IcingaWeb2\Widget\NameValueTable;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use InvalidArgumentException;
use ipl\Html\HtmlDocument;

class CustomValueDetails extends HtmlDocument
{
    use TranslationHelper;

    /** @var HostSystem|VirtualMachine */
    protected $object;

    public function __construct(BaseDbObject $object)
    {
        if (! $object instanceof HostSystem && ! $object instanceof VirtualMachine) {
            throw new InvalidArgumentException(
                'HostSystem or VirtualMachine expected, got ' . \get_class($object)
            );
        }
        $this->object = $object;
    }

    protected function assemble()
    {
        $object = $this->object;
        $this->prepend(new SubTitle($this->translate('Custom Values'), 'tags'));
        if ($values = $object->get('custom_values')) {
            $customValues = new NameValueTable();
            $customValues->addNameValuePairs(\json_decode($values));
            $this->add($customValues);
        } else {
            $this->add($this->translate('No custom values have been defined'));
        }
    }
}
