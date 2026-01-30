<?php

namespace Icinga\Module\Vspheredb\Application;

class MemoryLimit
{
    /**
     * @param string $string
     *
     * @return void
     */
    public static function raiseTo(string $string): void
    {
        $current = static::getBytes();
        $desired = static::parsePhpIniByteString($string);
        if ($current !== -1 && $current < $desired) {
            ini_set('memory_limit', $string);
        }
    }

    /**
     * @return int
     */
    public static function getBytes(): int
    {
        return static::parsePhpIniByteString((string) ini_get('memory_limit'));
    }

    /**
     * Return Bytes from PHP shorthand bytes notation
     *
     * http://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
     *
     * > The available options are K (for Kilobytes), M (for Megabytes) and G
     * > (for Gigabytes), and are all case-insensitive. Anything else assumes
     * > bytes.
     *
     * @param string $string
     *
     * @return int
     */
    public static function parsePhpIniByteString(string $string): int
    {
        $val = trim($string);

        if (preg_match('/^(\d+)([KMG])$/', $val, $m)) {
            $val = $m[1];
            switch ($m[2]) {
                case 'G':
                    $val *= 1024;
                    // Intentional fall-through
                    // no break
                case 'M':
                    $val *= 1024;
                    // Intentional fall-through
                    // no break
                case 'K':
                    $val *= 1024;
            }
        }

        return intval($val);
    }
}
