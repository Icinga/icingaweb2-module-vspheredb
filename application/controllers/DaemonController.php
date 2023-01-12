<?php

namespace Icinga\Module\Vspheredb\Controllers;

use gipfl\IcingaWeb2\Icon;
use gipfl\Json\JsonString;
use gipfl\Web\Widget\Hint;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Web\Form\LogLevelForm;
use Icinga\Module\Vspheredb\Web\Form\RestartDaemonForm;
use Icinga\Module\Vspheredb\Web\Table\ControlSocketConnectionsTable;
use Icinga\Module\Vspheredb\Format;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Table\VsphereApiConnectionTable;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;
use Icinga\Module\Vspheredb\WebUtil;
use Icinga\Web\Notification;
use ipl\Html\Html;
use ipl\Html\Table;

class DaemonController extends Controller
{
    use AsyncControllerHelper;

    public function indexAction()
    {
        $this->assertPermission('vspheredb/admin');
        $this->setAutorefreshInterval(30);
        $this->addTitle($this->translate('vSphereDB Daemon Status'));
        $this->handleTabs();
        $this->content()->add([
            Html::tag('h3', $this->translate('Damon Processes')),
            $this->prepareDaemonInfo(),
            Html::tag('h3', $this->translate('VMware API Connections')),
            $this->prepareVsphereConnectionTable(),
            Html::tag('h3', $this->translate('Pending HTTP Requests')),
            $this->prepareCurlInfoTable(),
            Html::tag('h3', $this->translate('Damon Log Output')),
            $this->prepareLogSettings(),
            $this->prepareLogWindow()
        ]);
    }

    protected function prepareLogSettings()
    {
        $logLevelForm = new LogLevelForm($this->remoteClient(), $this->loop());
        $logLevelForm->on($logLevelForm::ON_SUCCESS, function () {
            $this->redirectNow($this->url());
        });
        $logLevelForm->handleRequest($this->getServerRequest());
        if ($logLevelForm->talkedToSocket()) {
            return [$this->translate('Log level') . ': ', $logLevelForm];
        }

        return null;
    }

    protected function prepareDaemonInfo()
    {
        $db = $this->db()->getDbAdapter();
        $daemon = $db->fetchRow(
            $db->select()
                ->from('vspheredb_daemon')
                ->order('ts_last_refresh DESC')
                ->limit(1)
        );

        if ($daemon) {
            if ($daemon->ts_last_refresh / 1000 < time() - 10) {
                return Hint::error(Html::sprintf(
                    "Daemon keep-alive is outdated in our database, last refresh was %s",
                    WebUtil::timeAgo($daemon->ts_last_refresh / 1000)
                ));
            } else {
                $restartForm = new RestartDaemonForm($this->remoteClient(), $this->loop());
                $restartForm->on($restartForm::ON_SUCCESS, function () {
                    Notification::success('Daemon has been asked to restart');
                    $this->redirectNow($this->url());
                });
                $restartForm->handleRequest($this->getServerRequest());

                return [$restartForm, $this->prepareProcessTable(JsonString::decode($daemon->process_info))];
            }
        } else {
            return Hint::error($this->translate('Daemon is either not running or not connected to the Database'));
        }
    }

    protected function prepareProcessTable($processes)
    {
        $table = new Table();
        foreach ($processes as $pid => $process) {
            $table->add($table::row([
                [
                    Icon::create($process->running ? 'ok' : 'warning-empty'),
                    ' ',
                    $pid
                ],
                $process->command,
                Format::bytes($process->memory->rss)
            ]));
        }

        return $table;
    }

    protected function prepareLogWindow()
    {
        $db = $this->db()->getDbAdapter();
        $lineCount = 1000;
        $logLines = $db->fetchAll($db->select()
            ->from('vspheredb_daemonlog')
            ->order('ts_create DESC')
            ->limit($lineCount));
        $log = Html::tag('pre', ['class' => 'logOutput']);
        $logWindow = Html::tag('div', ['class' => 'logWindow'], $log);
        foreach ($logLines as $line) {
            $ts = $line->ts_create / 1000;
            if ($ts + 3600 * 16 < time()) {
                $tsFormatted = DateFormatter::formatDateTime($ts);
            } else {
                $tsFormatted = DateFormatter::formatTime($ts);
            }
            $log->add(Html::tag('div', [
                'class' => $line->level
            ], "$tsFormatted: " . $line->message));
        }

        return $logWindow;
    }

    protected function prepareConnectionTable()
    {
        try {
            return new ControlSocketConnectionsTable($this->syncRpcCall('connections.list'));
        } catch (\Exception $exception) {
            return Hint::error($exception->getMessage());
        }
    }

    protected function prepareCurlInfoTable()
    {
        try {
            $table = new Table();
            foreach ((array) $this->syncRpcCall('curl.getPendingConnections') as $connection) {
                $table->add(Table::row([
                    $connection->url,
                    isset($connection->request_header) ? Html::tag('a', [
                        'title' => preg_replace('/^(Cookie:).*$/im', '\1 ***', $connection->request_header)
                    ], preg_replace('/\n.+/s', '', $connection->request_header)) : '-',
                    \Icinga\Util\Format::seconds(floor($connection->total_time))
                ]));
            }
            if (count($table) === 0) {
                return $this->translate('Currently there are no requests pending');
            }
            return $table;
        } catch (\Exception $exception) {
            return Hint::error($exception->getMessage());
        }
    }

    protected function prepareVsphereConnectionTable()
    {
        try {
            $table = new VsphereApiConnectionTable(array_map(function ($row) {
                return [
                    'vCenterId' => $row->vCenterId,
                    'server'    => $row->server,
                    'state'     =>  $row->state . (isset($row->lastErrorMessage) ? ': ' . $row->lastErrorMessage : ''),
                ];
            }, $this->syncRpcCall('vsphere.getApiConnections')));
            if ($table->count() === 0) {
                return Hint::info($this->translate('The vSphereDB Daemon is currently not polling any vCenter'));
            }

            return $table;
        } catch (\Exception $exception) {
            return Hint::error($exception->getMessage());
        }
    }

    protected function handleTabs()
    {
        $action = $this->getRequest()->getControllerName();
        $tabs = $this->tabs(new MainTabs($this->Auth(), $this->db()));
        if ($tabs->has($action)) {
            $tabs->activate($action);
        } else {
            $this->redirectNow('vspheredb/configuration');
        }
    }
}
