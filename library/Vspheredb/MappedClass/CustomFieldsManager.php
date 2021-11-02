<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class CustomFieldsManager
{
    /** @var CustomFieldDef[] */
    public $field;

    protected $map;

    public function mapValues($values)
    {
        $map = $this->requireMap();
        $result = [];
        /** @var CustomFieldValue $value */
        foreach ($values as $value) {
            $key = $value->key;
            if (isset($map[$key])) {
                $result[$map[$key]] = $value->value;
            } else {
                $result[$key] = $value->value;
            }
        }

        return (object) $result;
    }

    protected function requireMap()
    {
        if ($this->map === null) {
            if (isset($this->field->CustomFieldDef)) { // Mapping goes wrong for arrays of X
                $fields = $this->field->CustomFieldDef;
            } else {
                var_dump($this);
                die('Custom Fields Manager is now correct');
            }
            $this->map = [];
            foreach ($fields as $field) {
                $this->map[$field->key] = $field->name;
            }
        }

        return $this->map;
    }
}
