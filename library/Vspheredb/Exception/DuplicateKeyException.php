<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Exception;

use Icinga\Exception\IcingaException;

// Stolen from Director, should be moved to incubator
class DuplicateKeyException extends IcingaException
{
}
