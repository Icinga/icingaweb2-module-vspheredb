<?php

namespace Icinga\Module\Vspheredb\Controllers;

use dipl\Html\Html;
use dipl\Html\Icon;
use dipl\Html\Table;
use Icinga\Date\DateFormatter;
use Icinga\Module\Vspheredb\Format;
use Icinga\Module\Vspheredb\Web\Controller;
use Icinga\Module\Vspheredb\Web\Tabs\MainTabs;

class DaemonController extends Controller
{
    public function indexAction()
    {
        $this->setAutorefreshInterval(1);
        $this->handleTabs();
        $db = $this->db()->getDbAdapter();
        $daemon = $db->fetchRow($db->select()->from('vspheredb_daemon')
            ->order('ts_last_refresh DESC')->limit(1)
        );
        $lineCount = 20;
        $logLines = $db->fetchAll($db->select()->from([
            'l' => $db->select()
                ->from('vspheredb_daemonlog')
                ->order('ts_create DESC')
                ->limit($lineCount)
        ])->order('ts_create ASC')->limit($lineCount));
        $log = Html::tag('pre', ['class' => 'logOutput']);
        $logWindow = Html::tag('div', ['class' => 'logWindow'], $log);
        foreach ($logLines as $line) {
            $log->add(Html::tag('div', [
                'class' => $line->level
            ], DateFormatter::formatDateTime($line->ts_create / 1000)
                . ': '
                . $line->message
            ));
        }

        if ($daemon) {
            $this->content()->add(
                $this->timeAgo($daemon->ts_last_refresh / 1000)
            );
            $processes = json_decode($daemon->process_info);
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
            $info = $table;
        } else {
            $info = Html::tag('p', ['class' => 'error'], 'Daemon is not running');
        }
        $this->content()->add([$info, $logWindow]);
    }

    protected function timeAgo($time)
    {
        return Html::tag('span', [
            'class' => 'time-ago',
            'title' => DateFormatter::formatDateTime($time)
        ], DateFormatter::timeAgo($time));
    }

    protected function handleTabs()
    {
        $action = $this->getRequest()->getControllerName();
        $tabs = $this->tabs(new MainTabs($this->db()));
        if ($tabs->has($action)) {
            $tabs->activate($action);
        } else {
            $this->redirectNow('vspheredb/configuration');
        }
    }
}
