<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\Web\Widget\Addon\VRangerBackupRunDetails;

class VRangerBackup extends SimpleBackupTool
{
    public const PREFIX = 'vRanger Backup & Replication:';

    protected $lastAttributes;

    public function getName()
    {
        return 'vRanger Backup & Replication';
    }

    /**
     * @return VRangerBackupRunDetails
     */
    public function getInfoRenderer()
    {
        return new VRangerBackupRunDetails($this);
    }

    protected function parseAnnotation($annotation)
    {
        $this->lastAttributes = null;
        $begin = strpos($annotation, static::PREFIX);
        if ($begin === false) {
            return;
        }

        $end = strpos($annotation, "\n", $begin);
        if ($end === false) {
            $end = strlen($annotation);
        }

        $realBegin = $begin + strlen(static::PREFIX);
        $match = substr($annotation, $realBegin, $end - $realBegin);

        if (preg_match_all('/\s([a-zA-Z\s]+)\s\[([^]]+)]/', $match, $m)) {
            $attributes = array_combine($m[1], $m[2]);
            if (array_key_exists('Time', $attributes)) {
                $attributes['Time'] = strtotime($attributes['Time']);
            }

            $this->lastAttributes = $attributes;
        }
    }
}
