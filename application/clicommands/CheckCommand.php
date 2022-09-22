<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use gipfl\Cli\Screen;
use Icinga\Date\DateFormatter;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Vspheredb\CheckPluginHelper;
use Icinga\Module\Vspheredb\Configuration;
use Icinga\Module\Vspheredb\Daemon\ConnectionState;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\Db\CheckRelatedLookup;
use Icinga\Module\Vspheredb\DbObject\BaseDbObject;
use Icinga\Module\Vspheredb\DbObject\Datastore;
use Icinga\Module\Vspheredb\DbObject\HostSystem;
use Icinga\Module\Vspheredb\DbObject\ManagedObject;
use Icinga\Module\Vspheredb\DbObject\VirtualMachine;
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState;
use Icinga\Module\Vspheredb\Monitoring\CheckResultSet;
use Icinga\Module\Vspheredb\Monitoring\Rule\Definition\RuleSetRegistry;
use Icinga\Module\Vspheredb\Monitoring\Rule\MonitoringRulesTree;
use Icinga\Module\Vspheredb\Monitoring\Rule\Settings;

/**
 * vSphereDB Check Command
 */
class CheckCommand extends Command
{
    use CheckPluginHelper;

    /** @var Db */
    protected $db;

    /**
     * Check vSphereDB daemon health
     */
    public function healthAction()
    {
        $this->run(function () {
            $client = new RemoteClient(Configuration::getSocketPath(), $this->loop());
            return $client->request('vsphere.getApiConnections')->then(function ($result) {
                $connState = new ConnectionState($result, $this->db()->getDbAdapter());
                $vCenters = $connState->getVCenters();
                $connections = $connState->getConnectionsByVCenter();
                foreach ($vCenters as $vcenter) {
                    $vcenterId = $vcenter->vcenter_id;
                    $prefix = sprintf('%s, %s: ', $vcenter->name, $vcenter->software);
                    if (isset($connections[$vcenterId])) {
                        foreach ($connections[$vcenterId] as $connection) {
                            if ($connection->enabled) {
                                $this->addProblem(
                                    ConnectionState::getIcingaState($connection->state),
                                    $prefix . ConnectionState::describe($connection->state, $connection->server)
                                );
                            } else {
                                $this->addMessage(
                                    "[DISABLED] $prefix"
                                    . ConnectionState::describe($connection->state, $connection->server)
                                );
                            }
                        }
                    } else {
                        $this->addProblem('WARNING', $prefix . ConnectionState::describeNoServer());
                    }
                }

                if (count($vCenters) > 1) {
                    if ($this->getState() === 0) {
                        $this->prependMessage('All vCenters/ESXi Hosts are connected');
                    } else {
                        $this->prependMessage('There are problems with some vCenters/ESXi Host connections');
                    }
                }
            }, function (\Exception $e) {
                $message = $e->getMessage();
                if (preg_match('/^Unable to connect/', $message)) {
                    $message = "Daemon not running? $message";
                }
                $this->addProblem('CRITICAL', $message);
            });
        });
    }

    /**
     * @deprecated
     */
    public function vcenterconnectionAction()
    {
        $this->addProblem('UNKNOWN', 'This check no longer exists. Please use `icingacli vspheredb check health`');
    }

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
            $host = $this->lookup()->findOneBy('HostSystem', [
                'host_name' => $this->params->getRequired('name')
            ]);
            $this->runChecks($host, 'host');
        });
    }

    /**
     * Check all Hosts
     *
     * USAGE
     *
     * icingacli vspheredb check hosts
     */
    public function hostsAction()
    {
        $this->showOverallStatusForProblems(
            $this->lookup()->listNonGreenObjects('HostSystem')
        );
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
            try {
                $vm = $this->lookup()->findOneBy('VirtualMachine', [
                    'object_name' => $this->params->getRequired('name')
                ]);
            } catch (NotFoundError $e) {
                $vm = $this->lookup()->findOneBy('VirtualMachine', [
                    'guest_host_name' => $this->params->getRequired('name')
                ]);
            }
            $this->runChecks($vm, 'vm');
        });
    }

    /**
     * Check all Virtual Machines
     *
     * USAGE
     *
     * icingacli vspheredb check vms
     */
    public function vmsAction()
    {
        $this->showOverallStatusForProblems(
            $this->lookup()->listNonGreenObjects('VirtualMachine')
        );
    }

    /**
     * Check Datastore Health
     *
     * USAGE
     *
     * icingacli vspheredb check datastore [--name <name>]
     */
    public function datastoreAction()
    {
        $this->run(function () {
            $datastore = $this->lookup()->findOneBy('Datastore', [
                'object_name' => $this->params->getRequired('name')
            ]);
            $this->runChecks($datastore, 'datastore');
        });
    }

    /**
     * Check all Datastores
     *
     * USAGE
     *
     * icingacli vspheredb check datastores
     */
    public function datastoresAction()
    {
        $this->showOverallStatusForProblems(
            $this->lookup()->listNonGreenObjects('Datastore')
        );
    }

    protected function runChecks(BaseDbObject $object, string $type)
    {
        $tree = new MonitoringRulesTree($this->db(), $type);
        $settings = $tree->getInheritedSettingsFor($object);
        $settings->setInternalDefaults(RuleSetRegistry::default());
        $all = new CheckResultSet(sprintf('%s, according configured rules', $this->getTypeLabelForObject($object)));
        foreach (RuleSetRegistry::default()->getSets() as $set) {
            if ($settings->isDisabled($set)) {
                continue;
            }
            $checkSet = new CheckResultSet($set->getLabel());
            $all->addResult($checkSet);
            foreach ($set->getRules() as $rule) {
                if ($settings->isDisabled($set, $rule)) {
                    continue;
                }
                if (!$rule::supportsObjectType($type)) {
                    continue;
                }
                $ruleSettings = $settings->withRemovedPrefix(Settings::prefix($set, $rule));
                foreach ($rule->checkObject($object, $ruleSettings) as $result) {
                    $checkSet->addResult($result);
                }
            }
        }
        echo $this->colorizeOutput($all->getOutput()) . PHP_EOL;
        exit($all->getState()->getExitCode());
    }

    protected function getTypeLabelForObject(BaseDbObject $object): string
    {
        if ($object instanceof HostSystem) {
            return 'Host System';
        } elseif ($object instanceof VirtualMachine) {
            return 'Virtual Machine';
        } elseif ($object instanceof Datastore) {
            return 'Datastore';
        }

        return 'Object';
    }

    protected function colorizeOutput(string $string): string
    {
        $screen = Screen::factory();
        $pattern = '/\[(OK|WARNING|CRITICAL|UNKNOWN)]\s/';
        return preg_replace_callback($pattern, function ($match) use ($screen) {
            return '[' .$screen->colorize($match[1], (new CheckPluginState($match[1]))->getColor()) . '] ';
        }, $string);
    }

    protected function showOverallStatusForProblems($problems)
    {
        $this->run(function () use ($problems) {
            if (empty($problems)) {
                $this->addMessage('Everything is fine');
            } else {
                foreach ($problems as $color => $objects) {
                    $this->raiseState($this->getStateForColor($color));
                    $this->addProblematicObjectNames($color, $objects);
                }
            }
        });
    }

    protected function addProblematicObjectNames($color, $objects)
    {
        $showMax = 5;
        $stateName = $this->getStateForColor($color);
        if (count($objects) === 1) {
            $name = array_shift($objects);
            $this->addProblem($stateName, sprintf('Overall status for %s is "%s"', $name, $color));
        } elseif (count($objects) <= $showMax) {
            $last = array_pop($objects);
            $this->addProblem($stateName, sprintf(
                'Overall status is "%s" for %s and %s',
                $color,
                implode(', ', $objects),
                $last
            ));
        } else {
            $names = array_slice($objects, 0, $showMax);
            $this->addProblem($stateName, sprintf(
                'Overall status is "%s" for %s and %d more',
                $color,
                implode(', ', $names),
                count($objects) - $showMax
            ));
        }
    }

    /**
     * @param ManagedObject $object
     * @return $this
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

    protected function getStateForColor($color)
    {
        $colors = [
            'green'  => 'OK',
            'gray'   => 'CRITICAL',
            'yellow' => 'WARNING',
            'red'    => 'CRITICAL',
        ];

        return $colors[$color];
    }

    /**
     * @param BaseDbObject $object
     * @return $this
     */
    protected function checkRuntimePowerState(BaseDbObject $object)
    {
        if ($object instanceof VirtualMachine) {
            $what = 'Virtual Machine';
        } elseif ($object instanceof HostSystem) {
            $what = 'Host System';
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
     * @param BaseDbObject $stats
     * @param BaseDbObject $object
     * @return $this
     */
    protected function checkUptime(BaseDbObject $stats, BaseDbObject $object)
    {
        if ($object->get('runtime_power_state') !== 'poweredOn') {
            // No need to check uptime
            return $this;
        }
        if ($stats->get('uptime') < 900) {
            $this->addProblem('WARNING', sprintf(
                'System booted %s ago',
                DateFormatter::formatDuration($stats->get('uptime'))
            ));
        }

        return $this;
    }

    protected function requireObject()
    {
        return ManagedObject::load($this->params->getRequired('name'), $this->db());
    }

    protected function lookup()
    {
        return new CheckRelatedLookup($this->db());
    }

    protected function db()
    {
        if ($this->db === null) {
            $this->db = Db::newConfiguredInstance();
        }

        return $this->db;
    }
}
