<?php

namespace Icinga\Module\Vspheredb\Web\Widget\Renderer;

class GuestToolsVersionRenderer
{
    public function __invoke($version)
    {
        if (\is_object($version)) {
            $version = $version->guest_tools_version;
        }        if (\preg_match('/^([89])(\d{1})(\d{2})$/', $version, $m)
            || \preg_match('/^(1\d)(\d{1})(\d{2})$/', $version, $m)
        ) {
            $version = \sprintf('%d.%d.%d', $m[1], $m[2], $m[3]);
        }

        return $version;
    }
}
