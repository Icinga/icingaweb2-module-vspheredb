<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\CheckPluginHelper;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\HostQuickStats;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\DbObject\VmQuickStats;

/**
 * vSphereDB Check Command
 */
class CheckCommand extends CommandBase
{
    use CheckPluginHelper;

    /**
     * Check Host Health
     *
     * USAGE
     *
     * icingacli vspheredb check host [--name <name>]
     */
    public function hostAction()
    {
        $this->run(function () {
            $db = Db::newConfiguredInstance();
            $host = HostSystem::findOneBy([
                'host_name' => $this->params->getRequired('name')
            ], $db);
            $quickStats = HostQuickStats::load($host->get('uuid'), $db);
            $this->checkOverallHealth($host->object())
                ->checkRuntimePowerState($host)
                ->checkUptime($quickStats);
        });
    }


    /**
     * Check Virtual Machine Health
     *
     * USAGE
     *
     * icingacli vspheredb check vm [--name <name>]
     */
    public function vmAction()
    {
        $this->run(function () {
            $db = Db::newConfiguredInstance();
            $vm = VirtualMachine::findOneBy([
                'guest_host_name' => $this->params->getRequired('name')
            ], $db);
            $quickStats = VmQuickStats::load($vm->get('uuid'), $db);
            $this->checkOverallHealth($vm->object())
                ->checkRuntimePowerState($vm)
                ->checkUptime($quickStats);
        });
    }

    /**
     * @param VirtualMachine $vm
     * @return $this
     * @throws \Icinga\Exception\IcingaException
     * @throws \Icinga\Exception\ProgrammingError
     */
    protected function checkOverallHealth(ManagedObject $object)
    {
        switch ($object->get('overall_status')) {
            case 'green':
                $this->prependMessage('Overall status is "green"');
                break;
            case 'gray':
                $this->addProblem('CRITICAL', 'Overall status is "gray", VM might be unreachable');
                break;
            case 'yellow':
                $this->addProblem('WARNING', 'Overall status is "yellow"');
                break;
            case 'red':
                $this->addProblem('CRITICAL', 'Overall status is "critical"');
                break;
            default:
                // Cannot happen
        }

        return $this;
    }

    /**
     * @param VirtualMachine $vm
     * @return $this
     * @throws \Icinga\Exception\IcingaException
     * @throws \Icinga\Exception\ProgrammingError
     */
    protected function checkRuntimePowerState(BaseDbObject $object)
    {
        if ($object instanceof VirtualMachine) {
            $what = 'Virtual Machine';
        } elseif ($object instanceof VirtualMachine) {
            $what = 'Virtual Machine';
        } else {
            $what = 'Object';
        }

        switch ($object->get('runtime_power_state')) {
            case 'poweredOff':
                $this->addProblem('CRITICAL', "$what has been powered off");
                break;
            case 'suspended':
                $this->addProblem('CRITICAL', "$what has been suspended");
                break;
            case 'unknown':
                $this->addProblem('UNKNOWN', "$what power state is unknown, might be disconnected");
                break;
            case 'poweredOn':
            default:
                // That's fine
                // Cannot happen
        }

        return $this;
    }

    /**
     * @param BaseDbObject $object
     * @return $this
     * @throws \Icinga\Exception\IcingaException
     * @throws \Icinga\Exception\ProgrammingError
     */
    protected function checkUptime(BaseDbObject $object)
    {
        if ($object->get('uptime') < 900) {
            $this->addProblem('WARNING', sprintf(
                'System booted %s ago',
                DateFormatter::formatDuration($object->get('uptime'))
            ));
        }

        return $this;
    }
}
