<?php

namespace Icinga\Module\Vspheredb;

use Icinga\Exception\AuthenticationException;
use Icinga\Module\Vspheredb\MappedClass\CustomFieldValue;

class CustomFieldsManager
{
    /** @var Api */
    protected $api;

    protected $obj;

    protected $map;

    public function __construct(Api $api)
    {
        $this->api = $api;
        $this->obj = $api->getServiceInstance()->customFieldsManager;
    }

    public function mapFields($values)
    {
        if (isset($values->CustomFieldValue)) {
            // Array is not mapped correctly :-/
            $values = $values->CustomFieldValue;
        }
        if (empty((array) $values)) {
            return null;
        }
        if ($this->map === null) {
            $this->prepareMap();
        }

        $result = [];
        /** @var CustomFieldValue $value */
        foreach ($values as $value) {
            $key = $value->key;
            if (isset($this->map[$key])) {
                $result[$this->map[$key]] = $value->value;
            } else {
                $result[$key] = $value->value;
            }
        }

        return (object) $result;
    }

    protected function prepareMap()
    {
        $object = $this->object();
        $fields = $object->field;
        if (isset($fields->CustomFieldDef)) { // Mapping goes wrong for arrays of X
            $fields = $fields->CustomFieldDef;
        }
        $this->map = [];
        foreach ($fields as $field) {
            $this->map[$field->key] = $field->name;
        }
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
