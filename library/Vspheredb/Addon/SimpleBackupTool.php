<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\CustomValues;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use RuntimeException;

abstract class SimpleBackupTool implements BackupTool
{
    public const PREFIX = 'no-such-prefix';

    protected $lastAttributes;

    protected $customValues = [];

    /**
     * @param $annotation
     * @return bool
     */
    public function wantsAnnotation($annotation)
    {
        return $annotation !== null && strpos($annotation, static::PREFIX) !== false;
    }

    /**
     * @param VirtualMachine $vm
     */
    public function handle(VirtualMachine $vm)
    {
        $this->parseAnnotation($vm->get('annotation'));
        $this->parseCustomValues($vm->customValues());
    }

    /**
     * Eventually override this method
     *
     * @param CustomValues $values
     */
    protected function parseCustomValues(CustomValues $values)
    {
        $attributes = [];
        foreach ($this->customValues as $name) {
            if ($values->has($name)) {
                $attributes[$name] = $values->get($name);
            }
        }

        if (! empty($attributes)) {
            $this->lastAttributes = $attributes;
        }
    }

    /**
     * @param VirtualMachine $vm
     * @return bool
     */
    public function wants(VirtualMachine $vm)
    {
        $values = $vm->customValues();
        foreach ($this->customValues as $name) {
            if ($values->has($name)) {
                return true;
            }
        }

        return $this->wantsAnnotation($vm->get('annotation'));
    }

    /**
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->lastAttributes;
    }

    /**
     * @return array
     */
    public function requireParsedAttributes()
    {
        $attributes = $this->getAttributes();
        if ($attributes === null) {
            throw new RuntimeException('Got no ' . $this->getName() . ' annotation info');
        }

        return $attributes;
    }

    protected function parseAnnotation($annotation)
    {
        if ($annotation === null) {
            return;
        }
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


        $parts = preg_split('/],\s/', rtrim($match, ']'));
        $attributes = [];
        foreach ($parts as $part) {
            if (strpos($part, ': [') === false) {
                continue;
            }
            [$key, $value] = preg_split('/:\s\[/', $part, 2);
            $attributes[trim($key)] = $value;
        }
        if (array_key_exists('Time', $attributes)) {
            $attributes['Time'] = strtotime($attributes['Time']);
        }
        $this->lastAttributes = $attributes;
    }

    public function stripAnnotation(&$annotation)
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

    public function stripCustomValues(CustomValues $values)
    {
        foreach ($this->customValues as $name) {
            $values->remove($name);
        }
    }

    public function removeCustomValues(CustomValues $values)
    {
        foreach ($this->customValues as $name) {
            $values->remove($name);
        }
    }
}
