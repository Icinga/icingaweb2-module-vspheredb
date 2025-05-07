<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Monitoring;

use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Vspheredb\ProvidedHook\HostDetailExtensionTrait;
use ipl\Html\ValidHtml;

class DetailviewExtension extends DetailviewExtensionHook
{
    use HostDetailExtensionTrait;

    public function getHtmlForObject(MonitoredObject $object): ?ValidHtml
    {
        if (! $object instanceof Host) {
            return null;
        }

        return $this->renderVObject($this->find($object, 'ido'));
    }

    protected function getCustomVar(object $host, string $customVar): ?string
    {
        return $host->customvars[$customVar] ?? null;
    }
}
