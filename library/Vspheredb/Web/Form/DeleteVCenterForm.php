<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\Widget\Hint;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use Icinga\Module\Vspheredb\Db;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Web\Notification;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;
use React\EventLoop\LoopInterface;

use function Clue\React\Block\await;

class DeleteVCenterForm extends Form
{
    use TranslationHelper;

    protected $defaultDecoratorClass = null;

    /** @var VCenter */
    protected $vCenter;

    /** @var RemoteClient */
    protected $client;

    /** @var LoopInterface */
    protected $loop;
    /**
     * @var Db
     */
    protected $db;

    public function __construct(Db $db, VCenter $vCenter, RemoteClient $client, LoopInterface $loop)
    {
        $this->db = $db;
        $this->vCenter = $vCenter;
        $this->client = $client;
        $this->loop = $loop;
    }

    public function assemble()
    {
        $this->add(Html::tag('h3', $this->translate('Delete this vCenter')));
        $this->add(Hint::warning($this->translate(
            'Deleting a vCenter means removing related information from the vSphereDB database.'
            . ' Apart from historic data (alerts, events) this step is reversible by simply'
            . ' redefining a Server Connection to this vCenter or ESXi Host.'
        )));
        (new NextConfirmCancel(
            NextConfirmCancel::buttonNext($this->translate('Delete')),
            NextConfirmCancel::buttonConfirm($this->translate('Really Delete')),
            NextConfirmCancel::buttonNext($this->translate('Cancel'))
        ))->addToForm($this);
    }

    public function onSuccess()
    {
        $db = $this->db->getDbAdapter();
        // Delete the connection first.
        $db->delete('vcenter_server', $db->quoteInto('vcenter_id = ?', (int) $this->vCenter->get('id')));

        try {
            if (await($this->client->request('db.deleteVcenter', [$this->vCenter->get('id')]))) {
                Notification::success($this->translate('vCenter data cleanup has been launched'));
            } else {
                Notification::success($this->translate('Failed to trigger vCenter data cleanup'));
            }
        } catch (\Exception $e) {
            Notification::error($e->getMessage());
        }
    }
}
