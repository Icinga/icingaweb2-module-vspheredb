<?php

namespace Icinga\Module\Vspheredb\Web\Table\Objects;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Link;
use Icinga\Module\Vspheredb\Polling\ApiConnection;
use Icinga\Module\Vspheredb\Web\Form\DisableServerForm;
use Icinga\Module\Vspheredb\Web\Form\EnableServerForm;
use Icinga\Module\Vspheredb\Web\Table\BaseTable;
use Icinga\Module\Vspheredb\Web\Table\SimpleColumn;
use ipl\Html\Html;
use Psr\Http\Message\RequestInterface;

class VCenterServersTable extends BaseTable implements EventEmitterInterface
{
    const ON_FORM_ACTION = 'formAction';

    use EventEmitterTrait;

    protected $request;

    protected $serverConnections;

    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function setServerConnections($connections)
    {
        $this->serverConnections = $connections;
    }

    /**
     * @param int $serverId
     * @param boolean $enabled
     * @return Icon
     */
    protected function getConnectionStatusIcon($serverId, $enabled)
    {
        if (isset($this->serverConnections[$serverId])) {
            $conn = end($this->serverConnections[$serverId]);
            switch ($conn->state) {
                case ApiConnection::STATE_CONNECTED:
                    return Icon::create('ok');
                case ApiConnection::STATE_LOGIN:
                case ApiConnection::STATE_INIT:
                    return Icon::create('spinner');
                case ApiConnection::STATE_FAILING:
                    return Icon::create('warning-empty');
                case ApiConnection::STATE_STOPPING:
                    return Icon::create('cancel');
            }

            return Icon::create('off');
        } else {
            if ($enabled) {
                return Icon::create('help');
            } else {
                return Icon::create('off');
            }
        }
    }

    protected function initialize()
    {
        $this->addAttributes([
            'class' => 'table-vcenter-servers',
        ]);
        $this->addAvailableColumns([
            (new SimpleColumn('server', $this->translate('Server'), [
                'id'       => 'vcs.id',
                'host'     => 'vcs.host',
                'username' => 'vcs.username',
                'scheme'   => 'vcs.scheme',
                'enabled'  => 'vcs.enabled',
                'vcenter'  => 'vc.name',
            ]))->setRenderer(function ($row) {
                $td = Html::tag('td', [
                    'class' => 'width-40'
                ]);

                $td->add(Link::create(
                    $this->makeUrl($row),
                    'vspheredb/vcenter/server',
                    ['id' => $row->id]
                ));
                $td->add(Html::tag('br'));
                $td->add($row->vcenter);

                return $td;
            })->setDefaultSortDirection('DESC'),
            (new SimpleColumn('enabled', $this->translate('Status'), [
                'vcs.enabled',
                'vcs.id',
            ]))->setRenderer(function ($row) {
                if ($row->enabled === 'y') {
                    $form = new DisableServerForm($row->id, $this->db());
                } else {
                    $form = new EnableServerForm($row->id, $this->db());
                }
                $form->addAttributes([
                    'data-base-target' => '_self'
                ]);
                $form->on($form::ON_SUCCESS, function () {
                    $this->emit(self::ON_FORM_ACTION);
                });
                $form->handleRequest($this->request);
                $form->ensureAssembled();
                $td = Html::tag('td', [
                    'class' => 'connection'
                ], $form);
                if ($this->serverConnections !== null) {
                    $td->add([' ', $this->getConnectionStatusIcon($row->id, $row->enabled === 'y'), ' ']);
                }
                return $td;
            }),
        ]);
    }

    public function renderRow($row)
    {
        $tr = parent::renderRow($row);
        if ($row->enabled === 'n') {
            $tr->getAttributes()->add('class', 'disabled');
        }

        return $tr;
    }

    protected function makeUrl($row)
    {
        return sprintf(
            '%s://%s@%s',
            $row->scheme,
            rawurlencode($row->username),
            $row->host
        );
    }

    public function prepareQuery()
    {
        return $this->db()->select()->from(
            ['vcs' => 'vcenter_server'],
            $this->getRequiredDbColumns()
        )->joinLeft(
            ['vc' => 'vcenter'],
            // 'vc.instance_uuid = vcs.vcenter_uuid',
            'vc.id = vcs.vcenter_id',
            []
        );
    }
}
