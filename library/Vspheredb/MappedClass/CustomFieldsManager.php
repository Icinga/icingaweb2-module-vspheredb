<?php

namespace Icinga\Module\Vspheredb\MappedClass;

#[\AllowDynamicProperties]
class CustomFieldsManager
{
    /** @var CustomFieldDef[] */
    public $field;

    protected $map;

    public function mapValues($values)
    {
        return static::mapValuesWithMap($values, $this->requireMap());
    }

    public static function mapValuesWithMap($values, array $map)
    {
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

    public function requireMap()
    {
        if ($this->map === null) {
            if (isset($this->field->CustomFieldDef)) { // Mapping goes wrong for arrays of X
                $fields = $this->field->CustomFieldDef;
            } else {
                // Used to be die('Custom Fields Manager is not correct');
                // However, this triggers on v5.5 (see #377)
                $fields = [];
            }
            $this->map = [];
            foreach ($fields as $field) {
                $this->map[$field->key] = $field->name;
            }
        }

        return $this->map;
    }
}
