<?php

namespace Icinga\Module\Vspheredb\Clicommands;

use Exception;
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
use Icinga\Module\Vspheredb\Monitoring\CheckPluginState;
use Icinga\Module\Vspheredb\Monitoring\CheckRunner;
use Icinga\Module\Vspheredb\Monitoring\Health\ServerConnectionInfo;
use Icinga\Module\Vspheredb\Monitoring\Health\VCenterInfo;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

use function React\Promise\resolve;

/**
 * vSphereDB Check Command
 */
class CheckCommand extends Command
{
    use CheckPluginHelper;

    /** @var ?Db */
    protected ?Db $db = null;

    /**
     * Check vSphereDB daemon health
     *
     * @return void
     */
    public function healthAction(): void
    {
        $this->run(function () {
            $migrations = Db::migrationsForDb($this->db());
            if (! $migrations->hasSchema()) {
                $this->addProblem('CRITICAL', 'Database has no vSphereDB schema');

                return resolve(null);
            }
            if ($migrations->hasPendingMigrations()) {
                $this->addProblem('WARNING', 'There are pending database schema migrations');
            };

            $this->checkDaemonStatus();
            $client = new RemoteClient(Configuration::getSocketPath(), $this->loop());
            return $client->request('vsphere.getApiConnections')->then(function ($result) {
                $connState = new ConnectionState($result, $this->db()->getDbAdapter());
                $vCenters = VCenterInfo::fetchAll($this->db()->getDbAdapter());
                $connections = $connState->getConnectionsByVCenter();
                foreach ($vCenters as $vcenter) {
                    $this->checkVCenterConnection($vcenter, $connections);
                }

                if (count($vCenters) > 1) {
                    if ($this->getState() === 0) {
                        $this->prependMessage('All vCenters/ESXi Hosts are connected');
                    } else {
                        $this->prependMessage('There are problems with some vCenters/ESXi Host connections');
                    }
                }
            }, function (Exception $e) {
                $message = $e->getMessage();
                if (str_starts_with($message, 'Unable to connect')) {
                    $message = "Daemon not running? $message";
                }
                $this->addProblem('CRITICAL', $message);
                $this->showOptionalTrace($e);
            });
        });
    }

    /**
     * Single vCenter Connection State
     *
     * This asks the daemon, whether there is a connection to the given vCenter
     *
     * USAGE
     *
     * icingacli vspheredb check vcenterconnection --vCenter <id>
     *
     * @return void
     */
    public function vcenterconnectionAction(): void
    {
        $this->run(function () {
            $vcenter = VCenterInfo::fetchOne(
                $this->requiredParam('vCenter'),
                Db::newConfiguredInstance()->getDbAdapter()
            );
            $client = new RemoteClient(Configuration::getSocketPath(), $this->loop());
            return $client->request('vsphere.getApiConnections')->then(function ($result) use ($vcenter) {
                $connState = new ConnectionState($result, $this->db()->getDbAdapter());
                $connections = $connState->getConnectionsByVCenter();
                $this->checkVCenterConnection($vcenter, $connections);
            });
        });
    }

    /**
     * Check Host Health
     *
     * USAGE
     *
     * icingacli vspheredb check host [--name <name>|--uuid <uuid>] [--ruleset <set>] [--rule [<ruleset>/]<rule>]
     *
     * @return void
     */
    public function hostAction(): void
    {
        $this->run(function () {
            $uuid = $this->params->get('uuid');
            if ($uuid !== null) {
                $params = [
                    'uuid' => Uuid::fromString($uuid)->getBytes()
                ];
            } else {
                $params = [
                    'host_name' => $this->params->getRequired('name')
                ];
            }
            $host = $this->lookup()->findOneBy('HostSystem', $params);
            $this->runChecks($host);
        });
    }

    /**
     * Check all Hosts
     *
     * USAGE
     *
     * icingacli vspheredb check hosts
     *
     * @return void
     */
    public function hostsAction(): void
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
     * icingacli vspheredb check vm [--name <name>|--uuid <uuid>] [--ruleset <set>] [--rule [<ruleset>/]<rule>]
     *
     * @return void
     */
    public function vmAction(): void
    {
        $this->run(function () {
            $uuid = $this->params->get('uuid');
            if ($uuid !== null) {
                $vm = $this->lookup()->findOneBy('VirtualMachine', [
                    'uuid' => Uuid::fromString($uuid)->getBytes()
                ]);
            } else {
                try {
                    $vm = $this->lookup()->findOneBy('VirtualMachine', [
                        'object_name' => $this->params->getRequired('name')
                    ]);
                } catch (NotFoundError $e) {
                    $vm = $this->lookup()->findOneBy('VirtualMachine', [
                        'guest_host_name' => $this->params->getRequired('name')
                    ]);
                }
            }
            $this->runChecks($vm);
        });
    }

    /**
     * Check all Virtual Machines
     *
     * USAGE
     *
     * icingacli vspheredb check vms
     *
     * @return void
     */
    public function vmsAction(): void
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
     * icingacli vspheredb check datastore [--name <name>|--uuid <uuid>] [--ruleset <set>] [--rule [<ruleset>/]<rule>]
     *
     * @return void
     */
    public function datastoreAction(): void
    {
        $this->run(function () {
            $uuid = $this->params->get('uuid');
            if ($uuid !== null) {
                $params = [
                    'uuid' => Uuid::fromString($uuid)->getBytes()
                ];
            } else {
                $params = [
                    'object_name' => $this->params->getRequired('name')
                ];
            }
            $datastore = $this->lookup()->findOneBy('Datastore', $params);
            $this->runChecks($datastore);
        });
    }

    /**
     * Check all Datastores
     *
     * USAGE
     *
     * icingacli vspheredb check datastores
     *
     * @return void
     */
    public function datastoresAction(): void
    {
        $this->showOverallStatusForProblems(
            $this->lookup()->listNonGreenObjects('Datastore')
        );
    }

    /**
     * @param BaseDbObject $object
     *
     * @return never
     */
    protected function runChecks(BaseDbObject $object): never
    {
        $runner = new CheckRunner($this->db());
        if ($section = $this->params->get(CheckRunner::RULESET_NAME_PARAMETER)) {
            self::assertString($section, '--' . CheckRunner::RULESET_NAME_PARAMETER);
            $runner->setRuleSetName($section);
        }
        if ($rule = $this->params->get(CheckRunner::RULE_NAME_PARAMETER)) {
            self::assertString($rule, '--' . CheckRunner::RULE_NAME_PARAMETER);
            if ($section === null && ($pos = strpos($rule, '/'))) {
                $section = substr($rule, 0, $pos);
                $runner->setRuleSetName($section);
                $rule = substr($rule, $pos + 1);
            }
            if ($rule !== '*') {
                $runner->setRuleName($rule);
            }
        }
        if ($this->params->get('inspect')) {
            $runner->enableInspection();
        }
        $result = $runner->check($object);
        echo $this->colorizeOutput($result->getOutput()) . PHP_EOL;

        exit($result->getState()->getExitCode());
    }

    /**
     * @param mixed  $string
     * @param string $label
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected static function assertString(mixed $string, string $label): void
    {
        if (! is_string($string)) {
            throw new InvalidArgumentException("$label must be a string");
        }
    }

    /**
     * @param VCenterInfo                                  $vcenter
     * @param array<int, array<int, ServerConnectionInfo>> $connections
     *
     * @return void
     */
    protected function checkVCenterConnection(VCenterInfo $vcenter, array $connections): void
    {
        $vcenterId = $vcenter->id;
        $prefix = sprintf('%s, %s: ', $vcenter->name, $vcenter->software);
        if (isset($connections[$vcenterId])) {
            foreach ($connections[$vcenterId] as $connection) {
                if ($connection->enabled) {
                    $this->addProblem(
                        $connection->getIcingaState(),
                        $prefix . ConnectionState::describe($connection)
                    );
                } else {
                    $this->addMessage(
                        "[DISABLED] $prefix"
                        . ConnectionState::describe($connection)
                    );
                }
            }
        } else {
            $this->addProblem('WARNING', $prefix . ConnectionState::describeNoServer());
        }
    }

    /**
     * @return void
     */
    protected function checkDaemonStatus(): void
    {
        $db = $this->db()->getDbAdapter();
        $daemon = $db->fetchRow(
            $db->select()->from('vspheredb_daemon')->order('ts_last_refresh DESC')->limit(1)
        );

        if ($daemon) {
            if ($daemon->ts_last_refresh / 1000 < time() - 10) {
                $this->addProblem('CRITICAL', sprintf(
                    "Daemon keep-alive is outdated, last refresh was %s",
                    DateFormatter::timeAgo($daemon->ts_last_refresh / 1000)
                ));
            }
        } else {
            $this->addProblem('CRITICAL', "Daemon is not writing to it's database");
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function colorizeOutput(string $string): string
    {
        $screen = Screen::factory();
        $pattern = '/\[(OK|WARNING|CRITICAL|UNKNOWN)]\s/';
        return preg_replace_callback($pattern, function ($match) use ($screen) {
            return '[' . $screen->colorize($match[1], (new CheckPluginState($match[1]))->getColor()) . '] ';
        }, $string);
    }

    /**
     * @param array $problems
     *
     * @return void
     */
    protected function showOverallStatusForProblems(array $problems): void
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

    /**
     * @param string $color
     * @param array  $objects
     *
     * @return void
     */
    protected function addProblematicObjectNames(string $color, array $objects): void
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
     * @param string $color
     *
     * @return string
     */
    protected function getStateForColor(string $color): string
    {
        return match ($color) {
            'green'       => 'OK',
            'gray', 'red' => 'CRITICAL',
            'yellow'      => 'WARNING'
        };
    }

    /**
     * @return CheckRelatedLookup
     */
    protected function lookup(): CheckRelatedLookup
    {
        return new CheckRelatedLookup($this->db());
    }

    /**
     * @return Db
     */
    protected function db(): Db
    {
        if ($this->db === null) {
            $this->db = Db::newConfiguredInstance();
        }

        return $this->db;
    }
}
