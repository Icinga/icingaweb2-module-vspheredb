<?php

namespace Icinga\Module\Vspheredb\Addon;

use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Web\Widget\Addon\IbmSpectrumProtectBackupRunDetails;
use RuntimeException;

class IbmSpectrumProtect implements BackupTool
{
    public const OPEN_TAG = '<Last Backup (IBM Spectrum Protect)>';

    public const CLOSE_TAG = '</Last Backup>';

    protected $lastAttributes;

    public function getName()
    {
        return 'IBM Spectrum Protect';
    }

    /**
     * @param VirtualMachine $vm
     * @return bool
     */
    public function wants(VirtualMachine $vm)
    {
        return $this->wantsAnnotation($vm->get('annotation'));
    }

    /**
     * @param VirtualMachine $vm
     */
    public function handle(VirtualMachine $vm)
    {
        $this->parseAnnotation($vm->get('annotation'));
    }

    /**
     * @return IbmSpectrumProtectBackupRunDetails
     */
    public function getInfoRenderer()
    {
        return new IbmSpectrumProtectBackupRunDetails($this);
    }

    /**
     * @param $annotation
     * @return bool
     */
    public function wantsAnnotation($annotation)
    {
        return $annotation !== null && strpos($annotation, static::OPEN_TAG) !== false;
    }

    /**
     * @return array
     */
    public function requireParsedAttributes()
    {
        $attributes = $this->getAttributes();
        if ($attributes === null) {
            throw new RuntimeException('Got no IBM Spectrum protect flags');
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
        $beginPos = strpos($annotation, static::OPEN_TAG);
        if ($beginPos === false) {
            $this->lastAttributes = null;

            return;
        }
        $begin = $beginPos + strlen(static::OPEN_TAG) + 1;
        $end = strpos($annotation, static::CLOSE_TAG, $begin);
        $lines = preg_split(
            '/\r?\n/',
            substr($annotation, $begin, $end - $begin),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        $attributes = [
            'Last Run Time'         => null, // '05/08/2018 23:22:06'
            'Status'                => null, // 'Successful'
            'Data Transmitted'      => null, // '1.58 GB', '0  B', '5.18 MB'
            'Duration'              => null, // '00:08:23'
            'Type'                  => null, // 'Incremental forever - Incremental'
                                             // 'Incremental forever - Full'
            'Schedule'              => null, // ' '
            'Data Mover'            => null, // 'WHATEVER_BLA_TEST_LOC'
            'Snapshot Type'         => null, // 'VMware Tools'
            'Application Protection' => null, // ' '
        ];

        foreach ($lines as $line) {
            if (strpos($line, '=') === false) {
                continue;
            }
            list($key, $value) = preg_split('/=/', $line, 2);
            if ($key === 'Last Run Time') {
                $attributes[$key] = static::parseTime($value);
            } elseif ($key === 'Data Transmitted') {
                $attributes[$key] = static::parseBytes($value);
            } elseif ($key === 'Duration') {
                $attributes[$key] = static::parseDuration($value);
            } else {
                $attributes[$key] = static::parseString($value);
            }
        }

        $this->lastAttributes = $attributes;
    }

    public function stripAnnotation(&$annotation)
    {
        $beginPos = strpos($annotation, static::OPEN_TAG);
        if ($beginPos === false) {
            return;
        }
        $begin = $beginPos + strlen(static::OPEN_TAG) + 1;
        $end = strpos($annotation, static::CLOSE_TAG, $begin);

        $annotation = substr($annotation, 0, $beginPos)
            . substr($annotation, $end + strlen(static::CLOSE_TAG));
    }

    /**
     * @param $string
     * @return string|null
     */
    public static function parseString($string)
    {
        if (strlen($string) < 2) {
            return $string;
        }

        if (preg_match("/^'(.*)'$/", $string, $match)) {
            return $match[1];
        } else {
            // Be strict. Otherwise we could of course return $string.
            return null;
        }
    }

    public static function parseDuration($value)
    {
        if (
            preg_match(
                '/^(\d{2}):(\d{2}):(\d{2})$/',
                static::parseString($value),
                $match
            )
        ) {
            return intval($match[1]) * 3600
                + intval($match[2]) * 60
                + intval($match[3]);
        } else {
            return null;
        }
    }

    public static function parseBytes($value)
    {
        $value = static::parseString($value);
        if ($value === null) {
            return null;
        }

        $byteMultipliers = [
            'B' => 1,
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024,
            'TB' => 1024 * 1024 * 1024 * 1024,
        ];

        if (preg_match('/^([0-9\.]+)\s+(B|KB|MB|GB|TB)$/', $value, $match)) {
            return (int) (sscanf($match[1], '%f')[0] * $byteMultipliers[$match[2]]);
        } else {
            return null;
        }
    }

    /**
     * @param $time
     * @return int|null
     */
    public static function parseTime($time)
    {
        $time = strtotime(static::parseString($time));
        if ($time === false) {
            return null;
        }

        return $time;
    }
}
