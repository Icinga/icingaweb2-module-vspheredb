<?php

namespace Icinga\Module\Vspheredb\ProvidedHook\Icingadb;

use Icinga\Module\Icingadb\Hook\HostDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Vspheredb\ProvidedHook\HostDetailExtensionTrait;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;

class HostDetailExtension extends HostDetailExtensionHook
{
    use HostDetailExtensionTrait;

    public function getHtmlForObject(Host $host): ValidHtml
    {
        return $this->renderVObject($this->find($host, 'icingadb'));
    }

    protected function getCustomVar(object $host, string $customVar): ?string
    {
        $var = $host->customvar->filter(Filter::equal('name', $customVar))->first();
        if ($var === null) {
            return null;
        }

        $decoded = json_decode($var->value);

        return is_scalar($decoded) ? $decoded : null;
    }
}
