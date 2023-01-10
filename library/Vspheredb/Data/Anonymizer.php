<?php

namespace Icinga\Module\Vspheredb\Data;

use Icinga\Application\Hook;
use Icinga\Module\Vspheredb\Hook\AnonymizerHook;

class Anonymizer
{
    /** @var ?AnonymizerHook */
    protected static $instance = null;

    public static function anonymizeString(?string $string): ?string
    {
        if ($instance = self::instance()) {
            return $instance->anonymizeString($string);
        }

        return $string;
    }

    public static function shuffleString(?string $string): ?string
    {
        if ($instance = self::instance()) {
            return $instance->shuffleString($string);
        }

        return $string;
    }

    protected static function instance(): ?AnonymizerHook
    {
        if (self::$instance === null) {
            $instance = Hook::first('vspheredb/Anonymizer');
            if ($instance === null) {
                self::$instance = false;
                return null;
            } else {
                self::$instance = $instance;
            }
        } elseif (self::$instance === false) {
            return null;
        }

        return self::$instance;
    }
}
