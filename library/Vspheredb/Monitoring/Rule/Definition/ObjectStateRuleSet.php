<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Monitoring\Rule\Definition;

class ObjectStateRuleSet extends MonitoringRuleSetDefinition
{
    public const RULE_CLASSES = [
        VMwareObjectStateRuleDefinition::class,
        PowerStateRuleDefinition::class,
    ];

    public function getLabel(): string
    {
        return $this->translate('Object State Policy');
    }

    public static function getIdentifier(): string
    {
        return 'ObjectStatePolicy';
    }
}
