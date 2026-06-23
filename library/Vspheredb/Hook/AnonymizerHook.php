<?php

// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Hook;

abstract class AnonymizerHook
{
    abstract public function anonymizeString(?string $string): ?string;

    abstract public function shuffleString(?string $string): ?string;
}
