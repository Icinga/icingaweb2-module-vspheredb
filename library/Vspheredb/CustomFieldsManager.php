<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\AuthenticationException;

class CustomFieldsManager
{
    /** @var Api */
    protected $api;

    protected $obj;

    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->obj = $api->getServiceInstance()->customFieldsManager;
    }

    /**
     * @return \Icinga\Module\Vspheredb\MappedClass\CustomFieldsManager
     * @throws \Icinga\Exception\AuthenticationException
     */
    public function object()
    {
        $result = $this->api->propertyCollector()->collectPropertiesEx([
            'propSet' => [
                'type' => 'CustomFieldsManager',
                'all'  => true
            ],
            'objectSet' => [
                'obj'  => $this->obj,
                'skip' => false
            ]
        ]);

        if (count($result->objects) !== 1) {
            throw new \RuntimeException(sprintf(
                'Exactly one CustomFieldsManager object expected, got %d',
                count($result->objects)
            ));

        }

        $object = $result->objects[0];

        if ($object->hasMissingProperties()) {
            if ($object->reportsNotAuthenticated()) {
                throw new AuthenticationException('Not authenticated');
            } else {
                // TODO: no permission, throw error message!
                throw new \RuntimeException('Got no result');
            }
        } else {
            return $object->toNewObject();
        }
    }
}
