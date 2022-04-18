<?php

namespace Icinga\Module\Vspheredb\Monitoring\Rule;

use Icinga\Module\Vspheredb\Db;

class RuleSetLoader
{
    /** @var MonitoringRulesTree */
    protected $tree;

    /** @var Db */
    protected $db;

    public function __construct(MonitoringRulesTree $tree, Db $db)
    {
        $this->tree = $tree;
        $this->db = $db;
    }

    public function getFlatSettingsForUuid($uuid): Settings
    {
        $uuids = $this->tree->listParentUuidsFor($uuid);
        $uuids[] = $uuid;
        return $this->getFlatSettingsForUuidList($uuids);
    }

    public function getFlatSettingsForParentsOfUuid($uuid): Settings
    {
        $uuids = $this->tree->listParentUuidsFor($uuid);
        return $this->getFlatSettingsForUuidList($uuids);
    }

    public function getFlatSettingsForUuidList(array $uuids): Settings
    {
        $settings = new Settings();
        foreach ($uuids as $uuid) {
            $ruleSet = MonitoringRuleSet::loadOptionalForUuid(
                $uuid,
                $this->tree->getBaseObjectFolderName(),
                $this->db
            );

            if ($ruleSet) {
                foreach ((array) $ruleSet->getSettings()->jsonSerialize() as $key => $value) {
                    $settings->set($key, $value);
                }
            }
        }

        return $settings;
    }
}
