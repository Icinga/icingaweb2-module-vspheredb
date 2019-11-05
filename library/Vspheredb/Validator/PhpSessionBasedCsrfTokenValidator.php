<?php

namespace Icinga\Module\Vspheredb\Validator;

class PhpSessionBasedCsrfTokenValidator extends SimpleValidator
{
    public function isValid($value)
    {
        if (\strpos($value, '|') === false) {
            return false;
        }

        list($seed, $token) = \explode('|', $value, 2);

        if (! \is_numeric($seed)) {
            return false;
        }

        if ($token === \hash('sha256', \session_id() . $seed)) {
            return true;
        } else {
            $this->addMessage('An invalid CSRF token has been submitted');
            return false;
        }
    }
}
