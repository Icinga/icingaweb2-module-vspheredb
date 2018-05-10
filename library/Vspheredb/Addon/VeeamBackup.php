<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Exception\IcingaException;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\Addon\VeeamBackupRunDetails;

class VeeamBackup implements BackupTool
{
    const PREFIX = 'Veeam Backup: ';

    protected $lastAttributes;

    public function getName()
    {
        return 'Veeam Backup & Replication';
    }

    /**
     * @param VirtualMachine $vm
     * @return bool
     * @throws IcingaException
     */
    public function wants(VirtualMachine $vm)
    {
        return $this->wantsAnnotation($vm->get('annotation'));
    }

    /**
     * @param VirtualMachine $vm
     * @throws IcingaException
     */
    public function handle(VirtualMachine $vm)
    {
        $this->parseAnnotation($vm->get('annotation'));
    }

    /**
     * @return VeeamBackupRunDetails
     * @throws IcingaException
     */
    public function getInfoRenderer()
    {
        return new VeeamBackupRunDetails($this);
    }

    /**
     * @param $annotation
     * @return bool
     */
    public function wantsAnnotation($annotation)
    {
        return strpos($annotation, static::PREFIX) !== false;
    }

    /**
     * @return array|null
     * @throws IcingaException
     */
    public function requireParsedAttributes()
    {
        $attributes = $this->getAttributes();
        if ($attributes === null) {
            throw new IcingaException('Got no Veeam Backup annotation info');
        }

        return $attributes;
    }

    /**
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->lastAttributes;
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


        $parts = preg_split('/\],\s/', rtrim($match, ']'));
        $attributes = [];
        foreach ($parts as $part) {
            if (strpos($part, ': [') === false) {
                continue;
            }
            list($key, $value) = preg_split('/:\s\[/', $part, 2);
            $attributes[trim($key)] = $value;
        }
        if (array_key_exists('Time', $attributes)) {
            $attributes['Time'] = strtotime($attributes['Time']);
        }
        $this->lastAttributes = $attributes;
    }

    public function stripAnnotation(& $annotation)
    {
        $begin = strpos($annotation, static::PREFIX);
        if ($begin === false) {
            return;
        }

        $end = strpos($annotation, "\n", $begin);
        if ($end === false) {
            $end = strlen($annotation);
        }

        $annotation = substr($annotation, 0, $begin)
        . substr($annotation, $end);
    }
}
