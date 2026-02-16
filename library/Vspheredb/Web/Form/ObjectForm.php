<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use gipfl\ZfDbStore\Store;
use Icinga\Authentication\Auth;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use gipfl\ZfDbStore\StorableInterface;

abstract class ObjectForm extends Form
{
    use TranslationHelper;

    /** @var Store */
    protected Store $store;

    /** @var ?StorableInterface */
    protected ?StorableInterface $object = null;

    /** @var ?class-string */
    protected ?string $class = null;

    /** @var bool */
    protected bool $wasNew = true;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->setMethod('POST');
    }

    public function setObject(StorableInterface $object): static
    {
        $this->object = $object;
        $this->populate($object->getProperties());
        $this->wasNew = $object->isNew();

        return $this;
    }

    /**
     * @return ?StorableInterface
     */
    public function getObject(): ?StorableInterface
    {
        return $this->object;
    }

    public function wasNew(): bool
    {
        return $this->wasNew;
    }

    public function isNew(): bool
    {
        return $this->object === null || $this->object->isNew();
    }

    protected function getObjectClass(): string
    {
        if ($this->class === null) {
            throw new RuntimeException(sprintf('ObjectForm %s defined no $class', get_class($this)));
        }

        return $this->class;
    }

    protected static function now(): float
    {
        $time = explode(' ', microtime());

        return round(1000 * ((int)$time[1] + (float)$time[0]));
    }

    public function onSuccess(): void
    {
        if ($this->object) {
            $object = $this->object;
            $object->setProperties($this->getValues());
        } else {
            $object = $this->createObject();
        }

        if ($object->hasProperty('ts_modified') && $object->isModified()) {
            $object->set('ts_modified', static::now());
        }
        if ($object->hasProperty('modified_by')) {
            $object->set('modified_by', Auth::getInstance()->getUser()->getUsername());
        }
        $this->store->store($object);
    }

    protected function createObject(): mixed
    {
        /** @var StorableInterface $class Not really an object, it's a class name */
        $class = $this->getObjectClass();
        $object = $class::create($this->getValues());
        $this->object = $object;

        if ($object->getKeyProperty() === 'uuid') {
            $object->set('uuid', Uuid::uuid4()->getBytes());
        }

        if ($object->hasProperty('ts_created')) {
            $object->set('ts_created', static::now());
        }
        if ($object->hasProperty('created_by')) {
            $object->set('created_by', Auth::getInstance()->getUser()->getUsername());
        }

        return $object;
    }
}
