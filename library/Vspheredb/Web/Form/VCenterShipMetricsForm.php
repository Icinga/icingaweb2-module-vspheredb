<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use gipfl\ZfDbStore\ZfDbStore;
use Icinga\Module\Vspheredb\Daemon\RemoteClient;
use Icinga\Module\Vspheredb\DbObject\VCenter;
use Icinga\Module\Vspheredb\Hook\PerfDataConsumerHook;
use Icinga\Module\Vspheredb\Storable\PerfdataConsumer;
use Icinga\Module\Vspheredb\Storable\PerfdataSubscription;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;
use React\EventLoop\LoopInterface;

class VCenterShipMetricsForm extends ObjectForm
{
    use FormElementStealer;
    use TranslationHelper;

    protected $class = PerfdataSubscription::class;

    /** @var VCenter */
    protected $vCenter;

    /** @var PerfdataConsumer[] */
    protected $consumers;

    /** @var RemoteClient */
    protected $remoteClient;

    /** @var LoopInterface */
    protected $loop;

    public function __construct(ZfDbStore $store, VCenter $vCenter, RemoteClient $client, LoopInterface $loop)
    {
        parent::__construct($store);
        $this->vCenter = $vCenter;
        $this->remoteClient = $client;
        $this->loop = $loop;
        $this->populate($vCenter->getProperties());
    }

    protected function fetchConsumers()
    {
        $db = $this->vCenter->getConnection()->getDbAdapter();
        /** @var PerfdataConsumer $consumers */
        $consumers = [];
        foreach ($db->fetchAll($db->select()->from('perfdata_consumer')) as $row) {
            $consumers[Uuid::fromBytes($row->uuid)->toString()] = PerfdataConsumer::create((array) $row);
        }

        return $consumers;
    }

    protected function enumConsumers($consumers)
    {
        $result = [];
        foreach ($consumers as $uuid => $consumer) {
            $result[$uuid] = $consumer->get('name');
        }

        return $result;
    }

    public function assemble()
    {
        $this->add(Html::tag('h3', $this->translate('Ship Performance Data')));
        if ($this->object instanceof PerfdataSubscription) {
            $this->populate((array) $this->object->settings());
        }

        if ($consumer = $this->selectConsumer()) {
            $this->addConsumerConfig($consumer);
        }
        $this->addButtons(isset($consumer), 'consumer');
    }

    /**
     * @return PerfdataConsumer|null
     */
    protected function selectConsumer()
    {
        if (! $this->isNew()) {
            return PerfdataConsumer::load($this->store, $this->object->get('consumer_uuid'));
        }
        $consumers = $this->fetchConsumers();
        $this->addElement('select', 'consumer', [
            'label' => $this->translate('Consumer'),
            'options' => [null => $this->translate('- please choose -')] + $this->enumConsumers($consumers),
            'description' => Html::sprintf(
                $this->translate('Choose one of your configured %s'),
                Link::create($this->translate('Performance Data Consumers'), 'vspheredb/perfdata/consumers')
            ),
            'class' => 'autosubmit',
        ]);
        $this->addElement('hidden', 'enabled', [
            'value' => 'y'
        ]);

        $value = $this->getValue('consumer');
        if (isset($consumers[$value])) {
            return $consumers[$value];
        }

        return null;
    }

    protected function addConsumerConfig($consumer)
    {
        $instance = PerfDataConsumerHook::createConsumerInstance($consumer, $this->loop);
        if ($form = $instance->getSubscriptionForm($this->remoteClient)) {
            $this->addFormElementsFrom($form);
        }
    }

    public function createObject()
    {
        $object = parent::createObject();
        $object->set('vcenter_uuid', $this->vCenter->get('instance_uuid'));

        return $object;
    }
}
