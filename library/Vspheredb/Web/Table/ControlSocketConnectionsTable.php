<?php

namespace Icinga\Module\Vspheredb\Web\Table;

use gipfl\IcingaWeb2\Icon;

class ControlSocketConnectionsTable extends ArrayTable
{
    protected $searchColumns = [
        'username',
        'socket',
    ];

    /* Sample Row:
    [0000000058294d05000000002f6007cc] => {
        [socket] => unix:///var/lib/icingaweb2/vspheredb.sock
        [direction] => in
        [namespaces] => [
            [0] => namespaces
            [1] => connections
            [2] => system
            [3] => vsphere
        ]
        [pid] => 2525
        [uid] => 998
        [gid] => 998
        [username] => icingaweb2
        [fullname] =>
        [groupname] => icingaweb2
        [type] => local
    }
    */

    protected $myPid;

    public function __construct($rows)
    {
        parent::__construct($rows);
        $this->myPid = posix_getpid();
    }

    public function renderRow($row)
    {
        $tr = $this::row([
            [
                $row->direction === 'in' ? Icon::create('endtime', [
                    'title' => $this->translate('Incoming connection'),
                ]) : Icon::create('starttime', [
                    'title' => $this->translate('Outgoing connection'),
                ]),
                ' ',
                $row->socket
            ],
            $row->username,
            $row->pid
        ]);
        if ($row->pid === $this->myPid) {
            $tr->addAttributes(['class' => 'control-socket-connections-table-row']);
        }

        return $tr;
    }

    public function getColumnsToBeRendered()
    {
        return [
            $this->translate('Socket'),
            $this->translate('User'),
            $this->translate('Client PID'),
        ];
    }
}
