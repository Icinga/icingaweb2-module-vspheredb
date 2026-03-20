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

    public const ON_DELETE = 'delete';

    protected ?string $class = PerfdataSubscription::class;

    /** @var VCenter */
    protected VCenter $vCenter;

    /** @var ?PerfdataConsumer[] */
    protected ?array $consumers = null;

    /** @var RemoteClient */
    protected RemoteClient $remoteClient;

    /** @var LoopInterface */
    protected LoopInterface $loop;

    public function __construct(ZfDbStore $store, VCenter $vCenter, RemoteClient $client, LoopInterface $loop)
    {
        parent::__construct($store);
        $this->vCenter = $vCenter;
        $this->remoteClient = $client;
        $this->loop = $loop;
        $this->populate($vCenter->getProperties());
    }

    /**
     * @return PerfdataConsumer[]
     */
    protected function fetchConsumers(): array
    {
        $db = $this->vCenter->getConnection()->getDbAdapter();
        /** @var PerfdataConsumer[] $consumers */
        $consumers = [];
        foreach ($db->fetchAll($db->select()->from('perfdata_consumer')) as $row) {
            $consumers[Uuid::fromBytes($row->uuid)->toString()] = PerfdataConsumer::create((array) $row);
        }

        return $consumers;
    }

    protected function enumConsumers($consumers): array
    {
        return array_map(fn ($consumer) => $consumer->get('name'), $consumers);
    }

    protected function assemble(): void
    {
        $this->add(Html::tag('h3', $this->translate('Ship Performance Data')));
        if ($this->object instanceof PerfdataSubscription && !$this->hasBeenSent()) {
            $this->populate((array) $this->object->settings());
        }

        if ($consumer = $this->selectConsumer()) {
            $this->addConsumerConfig($consumer);
        }
        $this->addButtons(isset($consumer), 'consumer');
    }

    /**
     * @return ?PerfdataConsumer
     */
    protected function selectConsumer(): ?PerfdataConsumer
    {
        $consumers = $this->fetchConsumers();
        $consumer = $this->object ? Uuid::fromBytes($this->object->get('consumer_uuid'))->toString() : null;
        $this->addElement('select', 'consumer', [
            'label' => $this->translate('Consumer'),
            'options' => ['' => $this->translate('- please choose -')] + $this->enumConsumers($consumers),
            'description' => Html::sprintf(
                $this->translate('Choose one of your configured %s'),
                Link::create($this->translate('Performance Data Consumers'), 'vspheredb/perfdata/consumers')
            ),
            'required' => true,
            'value' => $consumer,
            'class' => 'autosubmit'
        ]);
        $this->addHidden('enabled', ['value' => 'y']);

        $value = $this->getValue('consumer') ?? '';
        if (isset($consumers[$value])) {
            return $consumers[$value];
        }

        return null;
    }

    protected function addConsumerConfig($consumer): void
    {
        $instance = PerfDataConsumerHook::createConsumerInstance($consumer, $this->loop);
        if ($form = $instance->getSubscriptionForm($this->remoteClient)) {
            $this->addFormElementsFrom($form);
        }
    }

    public function isValidEvent($event)
    {
        if ($event === self::ON_DELETE) {
            return true;
        }

        return parent::isValidEvent($event);
    }

    public function createObject(): mixed
    {
        $object = parent::createObject();
        $object->set('vcenter_uuid', $this->vCenter->get('instance_uuid'));

        return $object;
    }
}
