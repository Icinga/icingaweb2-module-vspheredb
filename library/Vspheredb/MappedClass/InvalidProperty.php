<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\MappedClass;

class InvalidProperty extends Fault
{
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    public function getMessage()
    {
        return sprintf('Invalid Property: "%s"', $this->getName());
    }
}
