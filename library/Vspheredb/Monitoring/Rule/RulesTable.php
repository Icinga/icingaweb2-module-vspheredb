<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use ipl\Html\Table;

class RulesTable extends Table
{
    /** @var MonitoringRuleSet[] */
    protected $ruleSets;

    /**
     * @param MonitoringRuleSet[] $ruleSets
     */
    public function __construct(array $ruleSets)
    {
        $this->ruleSets = $ruleSets;
    }

    protected function assemble()
    {
        foreach ($this->ruleSets as $set) {
            $this->add(Table::row([
                $set->getDefinition()::getIdentifier()
            ]));
        }
    }
}
