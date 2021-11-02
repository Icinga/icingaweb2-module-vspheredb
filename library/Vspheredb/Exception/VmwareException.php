<?php

namespace Icinga\Module\Vspheredb\Exception;

use Exception;
use Icinga\Module\Vspheredb\MappedClass\MissingProperty;

class VmwareException extends Exception
{
    // TODO, how to combine many of then?
    // public $fault;

    /**
     * @param MissingProperty[] $missingSet
     */
    public static function forMissingSet($missingSet)
    {
        if (empty($missingSet)) {
            return new \RuntimeException('Trying to create an Exception for an empty missing set');
        }
        $paths = [];
        foreach ($missingSet as $missingProperty) {
            if (strlen($missingProperty->path)) {
                $paths[] = $missingProperty->path;
            }
        }
        if ($missingSet[0]->isNotAuthenticated()) {
            $self = new NotAuthenticatedException('Not authenticated');
            $self->paths = $paths;
        } elseif ($missingSet[0]->isNoPermission()) {
            $message = 'No permission';
            if (! empty($paths)) {
                $message .= ': ' . implode(', ', $paths);
            }
            $self = new NoPermissionException($message);
            $self->paths = $paths;
        } else {
            $self = new VmwareException('Generic exception when trying to fetch Content');
        }

        return $self;
    }
}
