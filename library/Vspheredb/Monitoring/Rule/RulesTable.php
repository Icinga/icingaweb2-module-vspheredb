<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use ipl\Html\Table;

class RulesTable extends Table
{
    /** @var MonitoringRuleSet[] */
    protected array $ruleSets;

    /**
     * @param MonitoringRuleSet[] $ruleSets
     */
    public function __construct(array $ruleSets)
    {
        $this->ruleSets = $ruleSets;
    }

    protected function assemble(): void
    {
        foreach ($this->ruleSets as $set) {
            $this->add(Table::row([$set->getDefinition()::getIdentifier()]));
        }
    }
}
